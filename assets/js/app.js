$(function () {
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
});

