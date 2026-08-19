import * as THREE from 'https://cdn.jsdelivr.net/npm/three@0.179.1/build/three.module.js';

const clamp=(v,a,b)=>Math.max(a,Math.min(b,v));
const esc=(v)=>String(v??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
const titleCase=(v)=>String(v??'').toLowerCase().replace(/(^|[\s\-/&])([\p{L}\p{N}])/gu,(m,p1,p2)=>p1+p2.toUpperCase());

function init(root){
  let cfg={};
  try{cfg=JSON.parse(root.dataset.r3dmbConfig||'{}')}catch(e){return}
  const urls=Array.isArray(cfg.pageUrls)?cfg.pageUrls:[];
  if(!urls.length)return;
  root.style.setProperty('--r3dmb-accent',cfg.accentColor||'#e4b956');

  const chapters=Array.isArray(cfg.chapters)?cfg.chapters:[];
  const showChapters=cfg.showChapters!==false;
  const showThumbs=cfg.showThumbnails!==false;
  root.innerHTML=`
    <section class="r3dmb-shell">
      <header class="r3dmb-head">
        <div>
          <div class="r3dmb-eyebrow">Interactive menu</div>
          <h2 class="r3dmb-title">${esc(cfg.heroTitle||'Speisekarte')}</h2>
          <p class="r3dmb-sub">${esc(cfg.heroSubtitle||'')}</p>
        </div>
      </header>
      <div class="r3dmb-layout${showChapters?'':' no-chapters'}">
        ${showChapters?'<aside class="r3dmb-chapters" aria-label="Menu chapters"></aside>':''}
        <main class="r3dmb-main">
          <div class="r3dmb-stage">
            <canvas class="r3dmb-canvas" aria-label="3D restaurant menu"></canvas>
            <div class="r3dmb-loading"><div class="r3dmb-loader-box"><div class="r3dmb-loader-title">Loading menu artwork</div><div class="r3dmb-loader-track"><div class="r3dmb-loader-bar"></div></div><div class="r3dmb-loader-num">0%</div></div></div>
            <div class="r3dmb-hint">Drag the page edge to turn</div>
          </div>
          <div class="r3dmb-controls">
            <button class="r3dmb-btn r3dmb-prev" type="button" aria-label="Previous page">‹</button>
            <div class="r3dmb-spread-label"></div>
            <div class="r3dmb-progress"><span></span></div>
            <button class="r3dmb-btn r3dmb-next" type="button" aria-label="Next page">›</button>
            <button class="r3dmb-btn r3dmb-zoom-out" type="button" aria-label="Zoom out">−</button>
            <button class="r3dmb-btn r3dmb-zoom-in" type="button" aria-label="Zoom in">+</button>
          </div>
          ${showThumbs?'<div class="r3dmb-thumbs" aria-label="Menu page thumbnails"></div>':''}
        </main>
      </div>
    </section>`;

  const canvas=root.querySelector('.r3dmb-canvas');
  const stage=root.querySelector('.r3dmb-stage');
  const loading=root.querySelector('.r3dmb-loading');
  const loaderBar=root.querySelector('.r3dmb-loader-bar');
  const loaderNum=root.querySelector('.r3dmb-loader-num');
  const prev=root.querySelector('.r3dmb-prev');
  const next=root.querySelector('.r3dmb-next');
  const zoomIn=root.querySelector('.r3dmb-zoom-in');
  const zoomOut=root.querySelector('.r3dmb-zoom-out');
  const spreadLabel=root.querySelector('.r3dmb-spread-label');
  const progressBar=root.querySelector('.r3dmb-progress span');
  const chapterWrap=root.querySelector('.r3dmb-chapters');
  const thumbs=root.querySelector('.r3dmb-thumbs');

  if(!window.WebGLRenderingContext){
    stage.innerHTML='<div class="r3dmb-fallback">Trình duyệt này không hỗ trợ WebGL.</div>';
    return;
  }

  if(chapterWrap){
    chapters.forEach((c,i)=>{
      const b=document.createElement('button');
      b.type='button'; b.className='r3dmb-chapter';
      b.innerHTML=`<span>${String(i+1).padStart(2,'0')}</span>${esc(titleCase(c.title||'Menu'))}`;
      b.addEventListener('click',()=>goToState(stateForPage(Number(c.page)||0)));
      chapterWrap.appendChild(b);
    });
  }
  if(thumbs){
    const states=Math.ceil(urls.length/2)+1;
    for(let s=0;s<states;s++){
      const pageIndex=s===0?0:Math.min(urls.length-1,s*2);
      const b=document.createElement('button'); b.type='button'; b.className='r3dmb-thumb';
      b.innerHTML=`<img src="${esc(urls[pageIndex])}" alt="Page ${pageIndex+1}"><span>${s===0?'Cover':Math.min(urls.length,pageIndex+1)}</span>`;
      b.addEventListener('click',()=>goToState(s)); thumbs.appendChild(b);
    }
  }

  const renderer=new THREE.WebGLRenderer({canvas,alpha:true,antialias:true,powerPreference:'high-performance'});
  renderer.setPixelRatio(Math.min(window.devicePixelRatio||1,2.5));
  renderer.outputColorSpace=THREE.SRGBColorSpace;
  renderer.toneMapping=THREE.ACESFilmicToneMapping;
  renderer.toneMappingExposure=1.04;
  renderer.shadowMap.enabled=true;
  renderer.shadowMap.type=THREE.PCFSoftShadowMap;

  const scene=new THREE.Scene();
  const camera=new THREE.PerspectiveCamera(34,1,.1,50);
  const book=new THREE.Group();
  scene.add(book);

  const ambient=new THREE.HemisphereLight(0xf7e6c7,0x140b06,1.8);scene.add(ambient);
  const key=new THREE.DirectionalLight(0xffe0aa,3.6);key.position.set(-3,5,8);key.castShadow=true;scene.add(key);
  const rim=new THREE.PointLight(0xc88b3d,25,16,2);rim.position.set(4,-1,5);scene.add(rim);

  const pageW=3.05, pageH=4.31;
  const coverMat=new THREE.MeshStandardMaterial({color:0x24160f,roughness:.56,metalness:.05});
  const coverEdgeMat=new THREE.MeshStandardMaterial({color:0x6d4521,roughness:.44,metalness:.14});
  const paperEdgeMat=new THREE.MeshStandardMaterial({color:0xd8c7a2,roughness:.78,metalness:0});

  function cover(side){
    const g=new THREE.Group();
    const body=new THREE.Mesh(new THREE.BoxGeometry(pageW*1.035,pageH*1.035,.12),coverMat);
    body.castShadow=true;body.receiveShadow=true;g.add(body);
    const frame=new THREE.Mesh(new THREE.BoxGeometry(pageW*1.005,pageH*1.005,.132),coverEdgeMat);
    frame.scale.set(1,.99,1);frame.position.z=-.004;g.add(frame);
    body.position.z=.012;
    g.position.set(side*(pageW/2+.045),0,-.115);
    return g;
  }
  book.add(cover(-1),cover(1));

  const leftStack=new THREE.Mesh(new THREE.BoxGeometry(pageW*.99,pageH*.99,.085),paperEdgeMat);
  leftStack.position.set(-pageW/2,0,-.04);leftStack.receiveShadow=true;book.add(leftStack);
  const rightStack=leftStack.clone();rightStack.position.x=pageW/2;book.add(rightStack);

  const spine=new THREE.Mesh(new THREE.BoxGeometry(.13,pageH*1.035,.13),coverEdgeMat);
  spine.position.set(0,0,-.05);spine.castShadow=true;spine.renderOrder=90;book.add(spine);
  const ringMat=new THREE.MeshStandardMaterial({color:0xb47a31,roughness:.24,metalness:.72,depthTest:false,depthWrite:false});
  [-1.48,-.5,.5,1.48].forEach(y=>{
    const ring=new THREE.Mesh(new THREE.TorusGeometry(.14,.025,10,34,Math.PI*2),ringMat);
    ring.rotation.x=Math.PI/2;ring.scale.y=.58;ring.position.set(0,y,.12);ring.castShadow=true;ring.renderOrder=100;book.add(ring);
  });

  const shadowPlane=new THREE.Mesh(new THREE.PlaneGeometry(9,7),new THREE.ShadowMaterial({color:0x000000,opacity:.34}));
  shadowPlane.position.set(0,-2.8,-.75);shadowPlane.rotation.x=-Math.PI/2;shadowPlane.receiveShadow=true;scene.add(shadowPlane);

  const manager=new THREE.LoadingManager();
  manager.onProgress=(url,loaded,total)=>{const p=Math.round(loaded/Math.max(1,total)*100);loaderBar.style.width=p+'%';loaderNum.textContent=p+'%'};
  manager.onLoad=()=>setTimeout(()=>loading.classList.add('is-hidden'),180);
  const textureLoader=new THREE.TextureLoader(manager);
  const maxAniso=renderer.capabilities.getMaxAnisotropy();
  const textures=urls.map(url=>{
    const t=textureLoader.load(url);
    t.colorSpace=THREE.SRGBColorSpace;
    t.anisotropy=Math.min(maxAniso,16);
    t.wrapS=t.wrapT=THREE.ClampToEdgeWrapping;
    t.magFilter=THREE.LinearFilter;
    t.minFilter=THREE.LinearMipmapLinearFilter;
    return t;
  });

  const blankCanvas=document.createElement('canvas');blankCanvas.width=16;blankCanvas.height=16;
  const bctx=blankCanvas.getContext('2d');bctx.fillStyle='#2b190f';bctx.fillRect(0,0,16,16);
  const blankTexture=new THREE.CanvasTexture(blankCanvas);blankTexture.colorSpace=THREE.SRGBColorSpace;

  const pageGeo=new THREE.PlaneGeometry(pageW,pageH,48,2);pageGeo.translate(pageW/2,0,0);
  const sheets=[];
  const sheetCount=Math.ceil(urls.length/2);
  const vertexShader=`
    uniform float uTurn;
    varying vec2 vUv;
    void main(){
      vUv=uv;
      vec3 p=position;
      float arch=sin(3.14159265*uv.x)*sin(3.14159265*uTurn);
      p.z += arch*.18;
      p.y += sin(3.14159265*uv.x)*sin(6.2831853*uTurn)*.014;
      gl_Position=projectionMatrix*modelViewMatrix*vec4(p,1.0);
    }`;
  const fragmentShader=`
    uniform sampler2D uFront;
    uniform sampler2D uBack;
    uniform float uTurn;
    varying vec2 vUv;
    void main(){
      vec4 c = gl_FrontFacing ? texture2D(uFront,vUv) : texture2D(uBack,vec2(1.0-vUv.x,vUv.y));
      float fold=sin(3.14159265*uTurn);
      float inner=1.0-.13*fold*(1.0-vUv.x);
      float edge=.975+.025*smoothstep(0.0,.035,vUv.x)*smoothstep(0.0,.035,1.0-vUv.x);
      c.rgb*=inner*edge;
      gl_FragColor=c;
    }`;

  for(let i=0;i<sheetCount;i++){
    const material=new THREE.ShaderMaterial({
      uniforms:{uFront:{value:textures[i*2]||blankTexture},uBack:{value:textures[i*2+1]||blankTexture},uTurn:{value:0}},
      vertexShader,fragmentShader,side:THREE.DoubleSide,transparent:false,depthWrite:true
    });
    const mesh=new THREE.Mesh(pageGeo.clone(),material);
    const group=new THREE.Group();group.add(mesh);book.add(group);
    sheets.push({group,mesh,material,progress:0,index:i});
  }

  let currentSheet=0;
  let queuedState=null;
  let turning=null;
  let drag=null;
  let zoomOffset=0;
  let pointerX=0,pointerY=0;

  function stateForPage(pageIndex){return clamp(Math.floor((pageIndex+1)/2),0,sheetCount)}
  function pageLabels(){
    if(currentSheet===0)return ['Cover','Page 1'];
    if(currentSheet===sheetCount)return [`Page ${urls.length}`,'Back cover'];
    return [`Page ${currentSheet*2}`,`Page ${currentSheet*2+1}`];
  }
  function visiblePageForChapter(){return currentSheet===0?0:Math.min(urls.length-1,currentSheet*2)}
  function updateUI(){
    const labels=pageLabels();spreadLabel.textContent=labels[0]+' / '+labels[1];
    prev.disabled=currentSheet<=0;next.disabled=currentSheet>=sheetCount;
    progressBar.style.width=(currentSheet/sheetCount*100)+'%';
    if(chapterWrap){
      const visible=visiblePageForChapter();let active=0;
      chapters.forEach((c,i)=>{if(Number(c.page)<=visible)active=i});
      Array.from(chapterWrap.children).forEach((el,i)=>el.classList.toggle('is-active',i===active));
      chapterWrap.children[active]?.scrollIntoView({block:'nearest'});
    }
    if(thumbs){Array.from(thumbs.children).forEach((el,i)=>el.classList.toggle('is-active',i===currentSheet));thumbs.children[currentSheet]?.scrollIntoView({behavior:'smooth',inline:'center',block:'nearest'})}
  }
  function refreshDepths(){
    sheets.forEach((s,i)=>{
      s.group.position.z=i<currentSheet ? .012+i*.002 : .012+(sheetCount-i)*.002;
    });
  }
  function startTurn(direction){
    if(turning||drag)return false;
    if(direction>0&&currentSheet>=sheetCount)return false;
    if(direction<0&&currentSheet<=0)return false;
    const index=direction>0?currentSheet:currentSheet-1;
    const sheet=sheets[index];
    turning={direction,index,sheet,start:performance.now(),from:sheet.progress,to:direction>0?1:0,duration:720};
    refreshDepths();return true;
  }
  function goToState(state){queuedState=clamp(Number(state)||0,0,sheetCount);if(!turning&&!drag&&queuedState!==currentSheet)startTurn(queuedState>currentSheet?1:-1)}

  prev.addEventListener('click',()=>goToState(currentSheet-1));
  next.addEventListener('click',()=>goToState(currentSheet+1));
  zoomIn.addEventListener('click',()=>zoomOffset=clamp(zoomOffset-.6,-1.8,2.2));
  zoomOut.addEventListener('click',()=>zoomOffset=clamp(zoomOffset+.6,-1.8,2.2));

  canvas.addEventListener('pointerdown',e=>{
    if(turning)return;
    const rect=canvas.getBoundingClientRect(),center=rect.left+rect.width/2;
    let mode,index;
    if(e.clientX>=center&&currentSheet<sheetCount){mode='forward';index=currentSheet}
    else if(e.clientX<center&&currentSheet>0){mode='backward';index=currentSheet-1}
    else return;
    queuedState=null;drag={mode,index,sheet:sheets[index],startX:e.clientX,rect,progress:sheets[index].progress};
    refreshDepths();canvas.classList.add('is-dragging');canvas.setPointerCapture?.(e.pointerId);e.preventDefault();
  });
  canvas.addEventListener('pointermove',e=>{
    const rect=canvas.getBoundingClientRect();pointerX=((e.clientX-rect.left)/rect.width-.5)*2;pointerY=((e.clientY-rect.top)/rect.height-.5)*2;
    if(!drag)return;
    const half=drag.rect.width*.48;
    let p;
    if(drag.mode==='forward')p=clamp((drag.startX-e.clientX)/half,0,1);
    else p=1-clamp((e.clientX-drag.startX)/half,0,1);
    drag.progress=p;drag.sheet.progress=p;e.preventDefault();
  });
  function endDrag(){
    if(!drag)return;
    const d=drag;drag=null;canvas.classList.remove('is-dragging');
    const commit=d.mode==='forward'?d.progress>.24:d.progress<.76;
    turning={direction:d.mode==='forward'?1:-1,index:d.index,sheet:d.sheet,start:performance.now(),from:d.sheet.progress,to:d.mode==='forward'?(commit?1:0):(commit?0:1),duration:480,commit};
  }
  canvas.addEventListener('pointerup',endDrag);canvas.addEventListener('pointercancel',endDrag);canvas.addEventListener('lostpointercapture',()=>drag&&endDrag());
  canvas.addEventListener('wheel',e=>{zoomOffset=clamp(zoomOffset+Math.sign(e.deltaY)*.24,-1.8,2.2);e.preventDefault()},{passive:false});

  const onKey=e=>{if(e.key==='ArrowRight')goToState(currentSheet+1);if(e.key==='ArrowLeft')goToState(currentSheet-1)};
  window.addEventListener('keydown',onKey);

  function resize(){
    const rect=stage.getBoundingClientRect();if(!rect.width||!rect.height)return;
    renderer.setSize(rect.width,rect.height,false);camera.aspect=rect.width/rect.height;camera.updateProjectionMatrix();
  }
  const ro=new ResizeObserver(resize);ro.observe(stage);resize();

  function smoothstep(t){return t*t*(3-2*t)}
  function render(now){
    if(turning){
      const t=clamp((now-turning.start)/turning.duration,0,1);const e=smoothstep(t);
      turning.sheet.progress=turning.from+(turning.to-turning.from)*e;
      if(t>=1){
        const tr=turning;turning=null;
        const completed=tr.commit!==false && Math.abs(tr.to-(tr.direction>0?1:0))<.001;
        if(completed)currentSheet+=tr.direction;
        refreshDepths();updateUI();
        if(queuedState!==null&&queuedState!==currentSheet)setTimeout(()=>startTurn(queuedState>currentSheet?1:-1),25);else queuedState=null;
      }
    }
    sheets.forEach((s,i)=>{
      const p=s.progress;
      s.group.rotation.y=-Math.PI*p;
      s.group.position.x=0;
      s.material.uniforms.uTurn.value=p;
      if(!turning&&!drag){s.progress=i<currentSheet?1:0}
    });

    const stageW=Math.max(1,stage.clientWidth);
    const stageH=Math.max(1,stage.clientHeight);
    const aspect=stageW/stageH;
    const vFov=THREE.MathUtils.degToRad(camera.fov);
    const bookFitW=pageW*2.14;
    const bookFitH=pageH*1.10;
    const fitHeight=(bookFitH*.5)/Math.tan(vFov*.5);
    const fitWidth=(bookFitW*.5)/(Math.tan(vFov*.5)*aspect);
    const fitZ=Math.max(fitHeight,fitWidth)*1.16;
    camera.position.x+=(0-camera.position.x)*.12;
    camera.position.z+=(fitZ+zoomOffset-camera.position.z)*.10;
    camera.position.y+=(0-camera.position.y)*.10;
    book.rotation.x+=(-.085-pointerY*.010-book.rotation.x)*.035;
    book.rotation.y+=((drag?0:pointerX*.010)-book.rotation.y)*.035;
    book.rotation.z+=(0-book.rotation.z)*.035;
    camera.lookAt(0,0,0);
    renderer.render(scene,camera);requestAnimationFrame(render);
  }
  refreshDepths();updateUI();requestAnimationFrame(render);
}

document.addEventListener('DOMContentLoaded',()=>document.querySelectorAll('.r3dmb-root').forEach(init));