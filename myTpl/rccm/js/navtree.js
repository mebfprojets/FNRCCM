// find all nav-tree's
$('.nav-tree').each(function () {

    // scope the tree
    var tree = $(this),
        groups = tree.find('.list-group'),
        items = tree.find('.list-group-item');

    // each list group item
    items.each(function(i, item){

        // toggle open class on caret mouse click
        $(item).children('b.caret').on('click', function (event) {
            $(item).toggleClass('open');
        });

        // toggle active class on item mouse click
        $(item).children('a').on('click', function (event) {

            // remove previous active item
            items.removeClass('active');

            // add active class to item
            $(item).addClass('active');
        });

        // jquery-ui extra (drag'n'drop)

/*      groups.sortable({
            connectWith: groups,
            revert: 50,
            cursorAt: { top: 1, left: 1 },
            helper: function (event, original) {
                return $('<div/>', { 'class': 'tree-item-drag' }).text($(original).children('a').children('span').text()).css({
                    fontWeight: 'bold',
                    padding: '4px 10px',
                    backgroundColor: '#ffffff',
                    border: '1px solid #d5d5d5',
                    boxShadow: '0 2px 4px rgba(0,0,0,0.2)',
					height: 28,
                    width: 'auto'
                });
            }
        });
        */
    });
});