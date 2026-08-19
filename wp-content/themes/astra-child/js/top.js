document.addEventListener('DOMContentLoaded', function () {
  const mv          = document.querySelector('.mv');
  if (!mv) return;
  const slides      = mv.querySelectorAll('.mv_slide');
  const dots        = mv.querySelectorAll('.mv_dot');
  const progressBar = mv.querySelector('.mv_progress-bar');
  const counter     = mv.querySelector('.mv_counter-current');
 
  let current    = 0;
  let timer      = null;
  const DURATION = 5000;
  const labels   = ['01', '02', '03'];
 
  function resetWrap(index) {
    const wrap = slides[index].querySelector('.mv_slide-wrap');
    wrap.style.transition      = 'none';
    wrap.style.transitionDelay = '0s';
    wrap.style.transform       = 'scale(1.12)';
  }
 
  function startWrapAnim(index) {
    const wrap = slides[index].querySelector('.mv_slide-wrap');
    wrap.style.transition = '';
    wrap.style.transform  = 'scale(1)';
  }
 
  function goTo(index) {
    const leaving = current;
  
    slides[leaving].classList.remove('is-active');
    dots[leaving].classList.remove('is-active');
  
    setTimeout(function() {
      resetWrap(leaving);
    }, 1200);
  
    current = index;
  
    resetWrap(current);
    slides[current].querySelector('.mv_slide-wrap').getBoundingClientRect();
    slides[current].classList.add('is-active');
    dots[current].classList.add('is-active');
    startWrapAnim(current);
  
    if (counter) counter.textContent = labels[current];
  
    progressBar.style.transition = 'none';
    progressBar.style.width      = '0%';
    progressBar.getBoundingClientRect();
    progressBar.style.transition = 'width ' + DURATION + 'ms linear';
    progressBar.style.width      = '100%';
  }
 
  function next() { goTo((current + 1) % slides.length); }
  function startAuto() { clearInterval(timer); timer = setInterval(next, DURATION); }
  function stopAuto()  { clearInterval(timer); }
 
  dots.forEach(function (dot) {
    dot.addEventListener('click', function () {
      const i = parseInt(this.dataset.index, 10);
      if (i === current) return;
      stopAuto(); goTo(i); startAuto();
    });
  });
 
  mv.addEventListener('mouseenter', stopAuto);
  mv.addEventListener('mouseleave', startAuto);
 
  let tx = 0;
  mv.addEventListener('touchstart', function (e) { tx = e.touches[0].clientX; stopAuto(); }, { passive: true });
  mv.addEventListener('touchend', function (e) {
    const d = tx - e.changedTouches[0].clientX;
    if (Math.abs(d) > 50) goTo(d > 0 ? (current+1)%slides.length : (current-1+slides.length)%slides.length);
    startAuto();
  }, { passive: true });
 
  resetWrap(0);
 
  requestAnimationFrame(function() {
    requestAnimationFrame(function() {
      slides[0].classList.add('is-active');
      dots[0].classList.add('is-active');
      startWrapAnim(0);
 
      progressBar.style.transition = 'none';
      progressBar.style.width      = '0%';
      progressBar.getBoundingClientRect();
      progressBar.style.transition = 'width ' + DURATION + 'ms linear';
      progressBar.style.width      = '100%';
 
      startAuto();
    });
  });
});

(function() {
  const isMobile = () => window.innerWidth <= 900;
  const container = document.getElementById('fmenuImgContainer');
  const activeName = document.getElementById('fmenuActiveName');
  const activePrice = document.getElementById('fmenuActivePrice');
  const items = document.querySelectorAll('.fmenu-item');
 
  const imgMap = {};
  items.forEach((item, i) => {
    const src = item.dataset.img;
    if (!src || imgMap[src]) return;
    const img = document.createElement('img');
    img.src = src;
    img.alt = item.querySelector('.fmenu-item-name')?.textContent || '';
    if (i === 0) img.classList.add('active');
    container.appendChild(img);
    imgMap[src] = img;
  });
 
  function activate(item) {
    items.forEach(el => el.classList.remove('active'));
    item.classList.add('active');
 
    const src = item.dataset.img;
    Object.values(imgMap).forEach(img => img.classList.remove('active'));
    if (imgMap[src]) imgMap[src].classList.add('active');
 
    const name = item.querySelector('.fmenu-item-name')?.textContent || '';
    const price = item.querySelector('.fmenu-item-price')?.textContent || '';
    activeName.textContent = name;
    activePrice.textContent = price;
  }
 
  items.forEach(item => {
    // Desktop: hover
    item.addEventListener('mouseenter', () => {
      if (!isMobile()) activate(item);
    });
 
    item.addEventListener('click', () => {
      if (isMobile()) {
        activate(item);
        document.querySelector('.fmenu-center').scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    });
  });
 
  if (items[0]) activate(items[0]);
})();