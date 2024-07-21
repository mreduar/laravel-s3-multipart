class t{constructor(t,a={}){this.file=t,this.options=a}async startUpload(t){const{name:a,type:e}=t;if(!a)throw new Error("Filename is empty");const{data:o}=await axios.get("/s3m/create-multipart-upload",{params:{filename:a,content_type:e}});return o}async upload(){try{const{key:t,uploadId:a,uuid:e}=await this.startUpload(this.file);if(!a)return void console.error("Upload ID not found");const o=this.options.progress||(()=>{}),s=await this.uploadChunks(this.file,t,a,o),n=await this.completeUpload(t,a,s);return o(100),{uuid:e,key:t,extension:this.file.name.split(".").pop(),name:this.file.name,url:n}}catch(t){console.error(t)}}async uploadChunks(a,e,o,s){var n=this;const r=this.options.chunk_size||t.DEFAULT_CHUNK_SIZE,i=this.options.max_concurrent_uploads||t.DEFAULT_MAX_CONCURRENT_UPLOADS,l=Math.ceil(a.size/r),p=new Array(l).fill(0),u=[];let c=0,d=0;const h=Array.from({length:i}).map(async function t(){if(d>=l)return;const h=d*r,m=Math.min(h+r,a.size),y=a.slice(h,m);c++,d++;const U=d,_=await n.uploadChunk(a,e,o,U,y,l,p,s);u.push(_),c--,c<i&&t()});for(await Promise.all(h);c>0;)console.log({activeUploads:c}),await new Promise(t=>setTimeout(t,100));return u.sort((t,a)=>t.PartNumber-a.PartNumber)}async completeUpload(t,a,e){const{data:{url:o}}=await axios.post("/s3m/complete-multipart-upload",{parts:e,upload_id:a,key:t});return o}async getSignUrl(t,a,e,o){const{data:{url:s}}=await axios.get("/s3m/create-sign-part",{params:{filename:t.name,content_type:t.type,part_number:o,upload_id:e,key:a}});return s}async uploadChunk(t,a,e,o,s,n,r,i){const l=await this.getSignUrl(t,a,e,o);return{ETag:(await axios.put(l,s,{headers:{"Content-Type":t.type},onUploadProgress:t=>this.handleUploadProgress(t,n,o-1,r,i)})).headers.etag,PartNumber:o}}handleUploadProgress(t,a,e,o,s){const n=Math.round(100*t.loaded/t.total);o[e]=n,s(Math.round(o.reduce((t,a)=>t+a)/a))}}function a(a,e){return new t(a,e).upload()}t.DEFAULT_CHUNK_SIZE=10485760,t.DEFAULT_MAX_CONCURRENT_UPLOADS=5;export{a as s3m};
