// country selector dropdown
$(document).on('click', '.lang-dropdown', function (e) {
	e.stopPropagation(); // Prevent event bubbling to the document
	var $dropdown = $('#lang-dropdown');

	if ($dropdown.css('display') == 'block') {
		closeDropDown('lang-dropdown');
	} else {
		upDropDown('lang-dropdown');
	}
});

// Handle click outside the dropdown
var $win = $(window);
var $box = $('#lang-dropdown');
$win.on('click.Bst', function (event) {
	if (
		!$box.is(event.target) && // Not the dropdown itself
		$box.has(event.target).length === 0 // Not a child of the dropdown
	) {
		closeDropDown('lang-dropdown');
	}
});

// Open the dropdown
function upDropDown(id) {
	$('#' + id).slideDown(600, function () {
		$(this).css('display', 'block');
	});
}

// Close the dropdown
function closeDropDown(id) {
	$('#' + id).slideUp(600, function () {
		$(this).css('display', 'none');
	});
}
// Handle item selection
$(document).on('click', '#lang-dropdown .item a', function (e) {
	e.preventDefault(); // prevent navigation
	var $selected = $(this).closest('.item'); // the clicked item
	var flag = $selected.find('img').attr('src'); // flag image
	var country = $(this).text(); // text label

	// update caption
	$('.lang-dropdown .caption').html(
		'<img src="' + flag + '" alt="flag"> ' + country + ' <img src="assets/images/header-dropdown.png" alt="dropdown">'
	);

	// close dropdown
	closeDropDown('lang-dropdown');
});

// shop page 
$(document).ready(function () {
	$(".size-option").click(function () {
		$(".size-option").removeClass("selected"); // remove from others
		$(this).addClass("selected"); // add to clicked one
	});
});

//   
// Currency selector dropdown toggle
$(document).on('click', '.curr-dropdown', function (e) {
	e.stopPropagation();
	var $dropdown = $('#curr-dropdown');

	if ($dropdown.css('display') == 'block') {
		closeDropDown('curr-dropdown');
	} else {
		upDropDown('curr-dropdown');
	}
});

// Handle outside click
var $win = $(window);
var $boxCurr = $('#curr-dropdown');
$win.on('click.Curr', function (event) {
	if (
		!$boxCurr.is(event.target) &&
		$boxCurr.has(event.target).length === 0
	) {
		closeDropDown('curr-dropdown');
	}
});

// Handle item selection
$(document).on('click', '#curr-dropdown .item a', function (e) {
	e.preventDefault();
	var currency = $(this).text();

	// update caption
	$('.curr-dropdown .caption').html(currency + ' <img src="assets/images/header-dropdown.png" alt="dropdown">');

	// close dropdown
	closeDropDown('curr-dropdown');
});