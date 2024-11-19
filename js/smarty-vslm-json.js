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
                    if (response.status === 404) {
                        return { status: 'rest_no_route', message: 'REST route not found.' };
                    }
                    throw new Error(`HTTP Error: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Data received:', data);
                const formattedJson = JSON.stringify(data, null, 2);
                container.innerHTML = `<pre style="color: #d9f2d9; background: #333; padding: 10px; border-radius: 5px; overflow: auto;">${formattedJson}</pre>`;
            })
            .catch(error => {
                console.error('Error fetching JSON:', error);
                const errorJson = {
                    status: 'error',
                    message: error.message || 'Unknown error occurred.',
                };
                const formattedErrorJson = JSON.stringify(errorJson, null, 2);
                container.innerHTML = `<pre style="color: #f8d7da; background: #333; padding: 10px; border-radius: 5px; overflow: auto;">${formattedErrorJson}</pre>`;
            });
    });
});
