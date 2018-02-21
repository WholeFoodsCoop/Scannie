$(document).ready(function() {
    /* working on maintaining aspect ratio
    $(window).resize(function() {
        $('.titleMandala img').each(function() {
            var maxWidth = 600; // Max width for the image
            var maxHeight = 600;    // Max height for the image
            var ratio = 0;  // Used for aspect ratio
            var width = $(this).width();    // Current image width
            var height = $(this).height();  // Current image height

            // Check if the current width is larger than the max
            if(width > maxWidth) {
                ratio = maxWidth / width;   // get ratio for scaling image
                $(this).css("width", maxWidth); // Set new width
                $(this).css("height", height * ratio);  // Scale height based on ratio
                height = height * ratio;    // Reset height to match scaled image
                width = width * ratio;    // Reset width to match scaled image
            }

            // Check if current height is larger than max
            if(height > maxHeight) {
                ratio = maxHeight / height; // get ratio for scaling image
                $(this).css("height", maxHeight);   // Set new height
                $(this).css("width", width * ratio);    // Scale width based on ratio
                width = width * ratio;    // Reset width to match scaled image
                height = height * ratio;    // Reset height to match scaled image
            }
        });
    });
    */
});
