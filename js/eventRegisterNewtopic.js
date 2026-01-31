document.addEventListener('DOMContentLoaded', () => {
    const topicForm = document.getElementById('topic-form').querySelector('form');
    const topicInput = document.getElementById('topic');
    const topicError = document.getElementById('error-text-topic');

    topicForm.addEventListener('submit', (e) => {
        if (topicInput.value.trim() === '' || topicInput.value.length > 256) {
            topicError.classList.remove('hidden');
            e.preventDefault();
        } else {
            topicError.classList.add('hidden');
        }
    });
});
