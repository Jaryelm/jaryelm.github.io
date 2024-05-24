window.addEventListener('DOMContentLoaded', function() {
    var programmingImg = document.getElementById('programming-img');
    programmingImg.addEventListener('mouseenter', function() {
      programmingImg.classList.add('programming-3d');
    });
    programmingImg.addEventListener('mouseleave', function() {
      programmingImg.classList.remove('programming-3d');
    });
    
    var parallaxContainer = document.querySelector('.parallax-container');
    var parallaxImg = document.querySelector('.parallax-img');
    
    parallaxContainer.addEventListener('mousemove', function(event) {
      var mouseX = event.clientX / window.innerWidth;
      var mouseY = event.clientY / window.innerHeight;
      var imgOffsetX = (mouseX - 0.5) * 30;
      var imgOffsetY = (mouseY - 0.5) * 30;
      parallaxImg.style.transform = `translate(${imgOffsetX}px, ${imgOffsetY}px) scale(1.1)`;
    });
  });
  