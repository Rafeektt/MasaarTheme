// category-drag-scroll.js
define([], function () {
  'use strict';

  return function () {
    const containers = document.querySelectorAll('.masaar-subnav-block .drag-scroll');

    containers.forEach(container => {
      const items = container.querySelectorAll('.subnav-item');
      items.forEach(item => {
        if (!item.querySelector('.drag-handle')) {
          const dragHandle = document.createElement('div');
          dragHandle.className = 'drag-handle';
          item.prepend(dragHandle);
        }
      });

      let isDragging = false;
      let startX, scrollLeft;

      if (window.innerWidth >= 769) {
        const startDrag = (e) => {
          if (e.target.classList.contains('drag-handle')) {
            isDragging = true;
            container.classList.add('user-is-dragging');
            startX = e.clientX;
            scrollLeft = container.scrollLeft;
            e.preventDefault();
          }
        };

        const handleMove = (e) => {
          if (!isDragging) return;
          const walk = (e.clientX - startX) * 1.5;
          container.scrollLeft = scrollLeft - walk;
          e.preventDefault();
        };

        const handleEnd = () => {
          isDragging = false;
          container.classList.remove('user-is-dragging');
        };

        container.addEventListener('mousedown', startDrag);
        document.addEventListener('mousemove', handleMove);
        document.addEventListener('mouseup', handleEnd);

        const observer = new MutationObserver(() => {
          if (!document.contains(container)) {
            document.removeEventListener('mousemove', handleMove);
            document.removeEventListener('mouseup', handleEnd);
            observer.disconnect();
          }
        });
        observer.observe(document.body, { childList: true, subtree: true });
      }

      const updateScrollState = () => {
        const { scrollLeft, scrollWidth, clientWidth } = container;
        container.classList.toggle('has-scroll-left', scrollLeft > 5);
        container.classList.toggle('has-scroll-right', scrollLeft < scrollWidth - clientWidth - 5);
      };

      container.addEventListener('scroll', updateScrollState, { passive: true });
      window.addEventListener('resize', updateScrollState, { passive: true });
      updateScrollState();
    });
  };
});
