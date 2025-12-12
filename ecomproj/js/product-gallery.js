//product gallery: swap main image when a thumbnail is clicked
document.addEventListener('DOMContentLoaded', function () {
    //main large product image element
    const mainImg = document.getElementById('product-main-image');
    if (!mainImg) return; //nothing to do if the page has no gallery

    //all thumbnail buttons under the main image
    const buttons = document.querySelectorAll('.product-thumb-btn');
    if (!buttons.length) return; //exit if there are no thumbnails

    //attach click handlers to each thumbnail
    buttons.forEach(btn => {
        btn.addEventListener('click', () => {
            //full-size image url is stored in data-full
            const full = btn.getAttribute('data-full');
            if (!full) return;

            //swap the main image source
            mainImg.src = full;

            //update active state so the user knows which thumb is selected
            buttons.forEach(b => b.classList.remove('product-thumb-btn--active'));
            btn.classList.add('product-thumb-btn--active');
        });
    });

    //mark the first thumbnail as active by default on page load
    buttons[0].classList.add('product-thumb-btn--active');
});
