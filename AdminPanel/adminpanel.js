document.addEventListener('DOMContentLoaded', function(){
  // Confirmation douce
  document.querySelectorAll('form[data-confirm]').forEach(function(form){
    form.addEventListener('submit', function(e){
      var msg = form.getAttribute('data-confirm') || "Confirmer l'action ?";
      if(!window.confirm(msg)) e.preventDefault();
    });
  });

  // Auto-hide des flash après 4s
  setTimeout(function(){
    document.querySelectorAll('.flash').forEach(function(el){
      el.style.transition='opacity .4s';
      el.style.opacity='0';
      setTimeout(function(){ el.remove(); }, 400);
    });
  }, 4000);
});
