document.addEventListener('DOMContentLoaded', () => {
    const uploadBtn = document.getElementById('uploadBtn');
    const imgPreview = document.getElementById('img-preview');
    const removeBtn = document.querySelector('.pro-pic-btn-remove');

    if (uploadBtn) {
        uploadBtn.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    imgPreview.src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    }

    if (removeBtn) {
        removeBtn.addEventListener('click', () => {
            uploadBtn.value = '';
            imgPreview.src = 'images/default.jpeg';
        });
    }
});
