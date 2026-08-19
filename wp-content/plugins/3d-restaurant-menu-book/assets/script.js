(function(){
  function esc(v){return String(v==null?'':v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;')}
  function price(v,s){var n=Number(v||0);return s+(Number.isFinite(n)?n.toFixed(2):esc(v))}
  function pageHTML(page,index,cfg){
    if(!page)return '<div class="r3dmb-page-inner"></div>';
    var items=Array.isArray(page.items)?page.items:[];
    var rows=items.map(function(it){
      var postId=it.postId?String(it.postId):'';
      var orderClass=postId?' menu-item':'';
      var data=postId?' data-post-id="'+esc(postId)+'"':'';
      return '<article class="r3dmb-item'+orderClass+'"'+data+'>'+
        '<div class="r3dmb-title-row menu-item__title-row">'+
          '<h4 class="r3dmb-item-name menu-item__name">'+(it.code?'<span class="r3dmb-code">'+esc(it.code)+'</span>':'')+esc(it.name||'')+(it.hasChoices?'<span class="r3dmb-choice"> · ab</span>':'')+'</h4>'+
          '<strong class="r3dmb-price">'+price(it.price,cfg.currencySymbol)+'</strong>'+
        '</div>'+
        (it.description?'<p class="r3dmb-desc">'+esc(it.description)+'</p>':'')+
      '</article>';
    }).join('');
    return '<div class="r3dmb-page-inner">'+
      '<div class="r3dmb-page-top"><span>'+esc(cfg.restaurantName)+'</span><span>PAGE '+String(index+1).padStart(2,'0')+'</span></div>'+
      '<h3 class="r3dmb-page-title">'+esc(page.title||page.category||'Menu')+'</h3>'+
      (page.image?'<div class="r3dmb-image"><img src="'+esc(page.image)+'" alt="'+esc(page.title||'Menu')+'"></div>':'')+
      '<div class="r3dmb-items">'+rows+'</div>'+
      (cfg.integrateOrder?'<div class="r3dmb-order-hint">Klicken Sie auf ein Gericht, um es zu bestellen.</div>':'')+
    '</div>';
  }
  function init(root){
    var cfg;try{cfg=JSON.parse(root.dataset.config||'{}')}catch(e){return}
    var pages=Array.isArray(cfg.pages)?cfg.pages:[]; if(!pages.length)return;
    root.style.setProperty('--r3dmb-accent',cfg.accentColor||'#c9c518');
    root.innerHTML='<section class="r3dmb-shell"><header class="r3dmb-head"><div><div class="r3dmb-eyebrow">3D FLIP BOOK MENU</div><h2 class="r3dmb-title">'+esc(cfg.heroTitle||'Our Menu')+'</h2><p class="r3dmb-sub">'+esc(cfg.heroSubtitle||'')+'</p></div></header><div class="r3dmb-layout"><aside class="r3dmb-nav"><h3>Menu</h3><div class="r3dmb-chapters"></div></aside><main class="r3dmb-stage"><div class="r3dmb-book-wrap"><div class="r3dmb-book"><div class="r3dmb-page r3dmb-page--left"></div><div class="r3dmb-spine"></div><div class="r3dmb-page r3dmb-page--right"></div><div class="r3dmb-flip"><div class="r3dmb-flip-face r3dmb-flip-front"></div><div class="r3dmb-flip-face r3dmb-flip-back"></div></div></div><div class="r3dmb-controls"><button class="r3dmb-btn r3dmb-prev">← Prev</button><div class="r3dmb-progress"></div><button class="r3dmb-btn r3dmb-next">Next →</button></div></div></main></div></section>';
    var left=root.querySelector('.r3dmb-page--left'),right=root.querySelector('.r3dmb-page--right'),chapters=root.querySelector('.r3dmb-chapters'),prev=root.querySelector('.r3dmb-prev'),next=root.querySelector('.r3dmb-next'),prog=root.querySelector('.r3dmb-progress'),flip=root.querySelector('.r3dmb-flip'),ff=root.querySelector('.r3dmb-flip-front'),fb=root.querySelector('.r3dmb-flip-back');
    var spread=0,maxSpread=Math.max(0,Math.ceil(pages.length/2)-1);
    pages.forEach(function(p,i){var b=document.createElement('button');b.type='button';b.className='r3dmb-chapter';b.innerHTML='<strong>'+esc(p.title||p.category||('Page '+(i+1)))+'</strong><small>'+((p.items||[]).length)+' món</small>';b.addEventListener('click',function(){render(Math.floor(i/2),true)});chapters.appendChild(b)});
    function render(s,animate){s=Math.max(0,Math.min(maxSpread,s));var li=s*2,ri=li+1;if(animate&&s!==spread&&innerWidth>1050){ff.innerHTML=pageHTML(pages[spread*2+1]||pages[spread*2],spread*2+1,cfg);fb.innerHTML=pageHTML(pages[li],li,cfg);flip.classList.remove('is-active');void flip.offsetWidth;flip.classList.add('is-active');setTimeout(function(){flip.classList.remove('is-active')},820)}spread=s;left.innerHTML=pageHTML(pages[li],li,cfg);right.innerHTML=pageHTML(pages[ri],ri,cfg);prog.textContent='Spread '+(s+1)+' / '+(maxSpread+1);prev.disabled=s===0;next.disabled=s===maxSpread;Array.from(chapters.children).forEach(function(el,i){el.classList.toggle('is-active',Math.floor(i/2)===s)})}
    prev.addEventListener('click',function(){render(spread-1,true)});next.addEventListener('click',function(){render(spread+1,true)});render(0,false);
  }
  document.addEventListener('DOMContentLoaded',function(){document.querySelectorAll('.r3dmb-root').forEach(init)});
})();
