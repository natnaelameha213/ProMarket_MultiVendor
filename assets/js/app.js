document.addEventListener('DOMContentLoaded',function(){
  // Sidebar toggle for dashboards
  var toggler=document.getElementById('sidebarToggle');
  var sidebar=document.querySelector('.dash-sidebar');
  var overlay=document.querySelector('.sidebar-overlay');
  if(toggler&&sidebar){
    toggler.addEventListener('click',function(){
      sidebar.classList.toggle('show');
      if(overlay) overlay.classList.toggle('show');
    });
    if(overlay) overlay.addEventListener('click',function(){
      sidebar.classList.remove('show');overlay.classList.remove('show');
    });
  }
});

// If a product photo fails to load, show the neutral placeholder instead of a broken icon
document.addEventListener('error',function(e){
  var el=e.target;
  if(el.tagName==='IMG'&&!el.dataset.fallback){
    el.dataset.fallback='1';
    el.src='data:image/svg+xml,'+encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="400" height="400"><rect fill="#EEF2F6" width="400" height="400"/><text x="200" y="205" text-anchor="middle" fill="#9FB3C8" font-family="sans-serif" font-size="16">Image unavailable</text></svg>');
  }
},true);

document.addEventListener('error', function (e) {
  var el = e.target;
  if (el.tagName === 'IMG' && !el.dataset.fallback) {
    el.dataset.fallback = '1';
    el.src = 'data:image/svg+xml,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="400" height="400"><rect fill="#102A43" width="400" height="400"/><polygon points="280,0 400,0 400,400 160,400" fill="#FF6B00"/><text x="150" y="210" text-anchor="middle" fill="#fff" font-family="sans-serif" font-size="24" font-weight="700">ProMarket</text></svg>');
  }
}, true);