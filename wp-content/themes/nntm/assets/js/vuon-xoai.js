(function(){'use strict';
function init(root){
  var slides=[].slice.call(root.querySelectorAll('[data-vx-slide]'));
  var prev=root.querySelector('[data-vx-prev]'), next=root.querySelector('[data-vx-next]');
  if(!slides.length||!prev||!next)return;
  var current=0, timer=null, paused=false;
  function paint(){ root.setAttribute('data-active',String(current)); slides.forEach(function(s,i){s.classList.toggle('is-active',i===current);}); }
  function go(delta){ current=(current+delta+slides.length)%slides.length; paint(); restart(); }
  function stop(){ if(timer){clearTimeout(timer);timer=null;} }
  function restart(){ stop(); if(paused||slides.length<2||matchMedia('(prefers-reduced-motion: reduce)').matches)return; timer=setTimeout(function(){go(1);},6000); }
  prev.addEventListener('click',function(){go(-1);}); next.addEventListener('click',function(){go(1);});
  root.addEventListener('mouseenter',function(){paused=true;stop();}); root.addEventListener('mouseleave',function(){paused=false;restart();});
  root.addEventListener('focusin',function(){paused=true;stop();}); root.addEventListener('focusout',function(){paused=false;restart();});
  paint(); restart();
}
function boot(){document.querySelectorAll('[data-vx-gallery]').forEach(init);} if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);else boot();
})();
