function selectPage(param, filters) {
	let form = document.createElement('form');
	form.method = 'post';
	form.action = '/openfood';
	filters = JSON.parse(filters);

	for (const key in filters) {
		if (filters.hasOwnProperty(key)) {
			const hiddenField = document.createElement('input');
			hiddenField.type = 'hidden';
			hiddenField.name = key;
			hiddenField.value = filters[key];

			form.appendChild(hiddenField);
		}
	}

	const hiddenField = document.createElement('input');
	hiddenField.type = 'hidden';
	hiddenField.name = 'page';
	hiddenField.value = param;

	form.appendChild(hiddenField);

	document.body.appendChild(form);
	form.submit();
}