<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\getJson;

beforeEach(function () {
    Config::set([
        'filesystems.disks.s3.bucket' => $_ENV['AWS_BUCKET'] = 'storage',
        'filesystems.disks.s3.key' => $_ENV['AWS_ACCESS_KEY_ID'] = 'key',
        'filesystems.disks.s3.region' => $_ENV['AWS_DEFAULT_REGION'] = 'us-east-1',
        'filesystems.disks.s3.secret' => $_ENV['AWS_SECRET_ACCESS_KEY'] = 'password',
        'filesystems.disks.s3.url' => $_ENV['AWS_URL'] = 'http://minio:9000',
        'filesystems.disks.s3.use_path_style_endpoint' => true,
    ]);

    Gate::define('uploadFiles', static function ($user = null, $bucket = null): bool {
        return true;
    });
});

afterEach(function () {
    Mockery::close();
});

test('response contains a upload id', function () {
    $mock = Mockery::mock('overload:'.Aws\S3\S3Client::class);

    $mock->shouldReceive('createMultipartUpload')->once()->andReturn([
        'UploadId' => 'example-upload-id',
    ]);

    $this->app->instance(Aws\S3\S3Client::class, $mock);

    getJson(route('s3m.create-multipart'))
        ->assertStatus(200)
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('uuid')
            ->has('bucket')
            ->has('key')
            ->has('uploadId')
            ->etc()
        );
});

it('data are validating', function () {
    $mock = Mockery::mock('overload:'.Aws\S3\S3Client::class);

    $mock->shouldReceive('createMultipartUpload')->once()->andReturn([
        'UploadId' => 'example-upload-id',
    ]);

    $this->app->instance(Aws\S3\S3Client::class, $mock);

    getJson(route('s3m.create-multipart', [
        'bucket' => 'test-bucket',
        'visibility' => 'public',
        'content_type' => 'image/jpeg',
        'cache_control' => 'max-age=31536000',
    ]))->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('uuid')
            ->has('key')
            ->where('bucket', 'test-bucket')
            ->where('uploadId', 'example-upload-id')
            ->etc()
        );

    getJson(route('s3m.create-multipart', [
        'bucket' => [
            'test-bucket',
        ],
        'visibility' => 'public',
        'content_type' => 'image/jpeg',
        'cache_control' => 'max-age=31536000',
    ]))->assertInvalid('bucket');
});
