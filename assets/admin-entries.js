(function ($) {
	const config = window.gonecontrolAdminEntries || {};
	const storageKeyPrefix = config.storageKeyPrefix || 'gonecontrol_entries_per_page_';
	const userId = typeof config.userId === 'number' ? config.userId : 0;
	const storageKey = `${storageKeyPrefix}${userId}`;

	const $table = $('.gone-control-admin table');
	if (!$table.length) {
		return;
	}

	const $rows = $table.find('tbody tr.gone-control-entry-row');
	const $pagination = $('.gone-control-pagination');
	if (!$rows.length || !$pagination.length) {
		return;
	}

	const $perPageInput = $pagination.find('.gone-control-per-page');
	const $prev = $pagination.find('.gone-control-prev');
	const $next = $pagination.find('.gone-control-next');
	const $pageInput = $pagination.find('.gone-control-page-input');
	const $total = $pagination.find('.gone-control-page-total');

	let perPage = parseInt(window.localStorage.getItem(storageKey), 10);
	if (!perPage || perPage < 1) {
		perPage = parseInt($perPageInput.data('default'), 10) || 200;
	}

	$perPageInput.val(perPage);

	let currentPage = 1;

	const getTotalPages = () => Math.max(1, Math.ceil($rows.length / perPage));

	const render = () => {
		const totalPages = getTotalPages();
		if (currentPage > totalPages) {
			currentPage = totalPages;
		}

		const startIndex = (currentPage - 1) * perPage;
		const endIndex = startIndex + perPage;

		$rows.each((index, row) => {
			$(row).toggle(index >= startIndex && index < endIndex);
		});

		$prev.prop('disabled', currentPage <= 1);
		$next.prop('disabled', currentPage >= totalPages);
		$pageInput.val(currentPage);
		$total.text(`of ${totalPages}`);
	};

	$prev.on('click', () => {
		if (currentPage > 1) {
			currentPage -= 1;
			render();
		}
	});

	$next.on('click', () => {
		if (currentPage < getTotalPages()) {
			currentPage += 1;
			render();
		}
	});

	$perPageInput.on('change', () => {
		let value = parseInt($perPageInput.val(), 10);
		if (!value || value < 1) {
			value = 1;
			$perPageInput.val(value);
		}

		perPage = value;
		window.localStorage.setItem(storageKey, String(perPage));
		currentPage = 1;
		render();
	});

	$pageInput.on('change', () => {
		let value = parseInt($pageInput.val(), 10);
		if (!value || value < 1) {
			value = 1;
		}

		currentPage = Math.min(value, getTotalPages());
		render();
	});

	render();
})(jQuery);
