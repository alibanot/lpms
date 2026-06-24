$(function () {
    const cleanupBackdrops = function () {
        const hasOpenOverlay = $('.offcanvas.show, .modal.show').length > 0;
        if (!hasOpenOverlay) {
            $('.offcanvas-backdrop, .modal-backdrop').remove();
            $('body').removeClass('modal-open offcanvas-backdrop').css({
                overflow: '',
                paddingRight: '',
            });
        }
    };

    const closeDesktopOffcanvas = function () {
        if (!window.matchMedia('(min-width: 992px)').matches) {
            return;
        }

        const sidebar = document.getElementById('mobileSidebar');
        if (!sidebar || !window.bootstrap) {
            cleanupBackdrops();
            return;
        }

        const instance = bootstrap.Offcanvas.getInstance(sidebar);
        if (instance) {
            instance.hide();
        }

        sidebar.classList.remove('show');
        cleanupBackdrops();
    };

    window.addEventListener('pageshow', cleanupBackdrops);
    window.addEventListener('resize', closeDesktopOffcanvas);
    document.addEventListener('hidden.bs.offcanvas', cleanupBackdrops);

    closeDesktopOffcanvas();

    $('.datatable').DataTable({
        responsive: true,
        pageLength: 10,
        order: [],
    });

    $('[data-calc-total]').each(function () {
        const $scope = $(this);
        const update = function () {
            const qty = parseFloat($scope.find('[data-qty]').val()) || 0;
            const price = parseFloat($scope.find('[data-unit-price]').val()) || 0;
            $scope.find('[data-total]').val((qty * price).toFixed(2));
        };
        $scope.on('input', '[data-qty], [data-unit-price]', update);
        update();
    });

    $('[data-event-balance]').each(function () {
        const $scope = $(this);
        const update = function () {
            const price = parseFloat($scope.find('[data-event-price]').val()) || 0;
            const deposit = parseFloat($scope.find('[data-event-deposit]').val()) || 0;
            const balancePaid = parseFloat($scope.find('[data-event-balance-paid]').val()) || 0;
            $scope.find('[data-event-balance-output]').val(Math.max(0, price - deposit - balancePaid).toFixed(2));
        };
        $scope.on('input', '[data-event-price], [data-event-deposit], [data-event-balance-paid]', update);
        update();
    });

    $('[data-report-date]').on('change', function () {
        $('#reportFilter').val('custom');
    });

    $('[data-frozen-stock]').each(function () {
        const $scope = $(this);
        const updateExpiry = function () {
            const made = $scope.find('[data-date-made]').val();
            if (!made) {
                return;
            }
            const expiry = new Date(made + 'T00:00:00');
            expiry.setMonth(expiry.getMonth() + 2);
            $scope.find('[data-expiry-date]').val(expiry.toISOString().slice(0, 10));
        };
        const updateTotal = function () {
            const remaining = parseInt($scope.find('[data-stock-remaining]').val(), 10) || 0;
            const pieces = parseInt($scope.find('[data-stock-pieces]').val(), 10) || 0;
            $scope.find('[data-stock-total]').val(remaining * pieces);
        };
        $scope.on('change', '[data-date-made]', updateExpiry);
        $scope.on('input', '[data-stock-remaining], [data-stock-pieces]', updateTotal);
        updateTotal();
    });
});
