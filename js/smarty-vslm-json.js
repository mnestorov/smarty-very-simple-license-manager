document.addEventListener('DOMContentLoaded', function () {
    console.log('DOMContentLoaded triggered');
    const jsonContainers = document.querySelectorAll('[data-json-endpoint]');

    console.log('Found containers:', jsonContainers);

    jsonContainers.forEach(container => {
        const endpoint = container.dataset.jsonEndpoint;
        console.log('Fetching from endpoint:', endpoint);

        fetch(endpoint)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP Error ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Data received:', data);
                container.innerHTML = `<pre style="color: #d9f2d9;">${JSON.stringify(data, null, 2)}</pre>`;
            })
            .catch(error => {
                console.error('Error fetching JSON:', error);
                container.innerHTML = `<p style="color: #f8d7da;">Error fetching JSON! ${error.message}</p>`;
            });
    });
});
