console.log('hello')
const button = document.getElementById('generate');
if (button) {
    button.addEventListener('click', event => {
        event.preventDefault();
        console.log('click')
    })
}