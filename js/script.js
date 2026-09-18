function toggleMenu(){
  const nav=document.getElementById('mainNav');
  if(nav) nav.classList.toggle('open');
}
function confirmDelete(message){
  return window.confirm(message || '¿Confirmar esta operación?');
}
document.addEventListener('DOMContentLoaded',()=>{
  const alerts=document.querySelectorAll('.alert.success');
  alerts.forEach(a=>setTimeout(()=>{a.style.opacity='0'; setTimeout(()=>a.remove(),400)},5000));
});