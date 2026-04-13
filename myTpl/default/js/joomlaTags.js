jQuery(document).ready(function (){
jQuery('#jform_tags').chosen({
disable_search_threshold : 10,
allow_single_deselect : true
});
});
(function($){
$(document).ready(function () {
$('#jform_tags').ajaxChosen({
type: 'GET',
url: 'http://localhost/monpulsar/index.php?option=com_tags&task=tags.searchAjax',
dataType: 'json',
jsonTermKey: 'like',
afterTypeDelay: '500',
minTermLength: '3'
}, function (data) {
var results = [];
$.each(data, function (i, val) {
results.push({ value: val.value, text: val.text });
});
return results;
});
});
})(jQuery);
(function($){
$(document).ready(function () {
var customTagPrefix = '#new#';
// Method to add tags pressing enter
$('#jform_tags_chzn input').keydown(function(event) {
// Tag is greater than 3 chars and enter pressed
if (this.value.length >= 3 && (event.which === 13 || event.which === 188)) {
// Search an highlighted result
var highlighted = $('#jform_tags_chzn').find('li.active-result.highlighted').first();
// Add the highlighted option
if (event.which === 13 && highlighted.text() !== '')
{
// Extra check. If we have added a custom tag with this text remove it
var customOptionValue = customTagPrefix + highlighted.text();
$('#jform_tags option').filter(function () { return $(this).val() == customOptionValue; }).remove();
// Select the highlighted result
var tagOption = $('#jform_tags option').filter(function () { return $(this).html() == highlighted.text(); });
tagOption.attr('selected', 'selected');
}
// Add the custom tag option
else
{
var customTag = this.value;
// Extra check. Search if the custom tag already exists (typed faster than AJAX ready)
var tagOption = $('#jform_tags option').filter(function () { return $(this).html() == customTag; });
if (tagOption.text() !== '')
{
tagOption.attr('selected', 'selected');
}
else
{
var option = $('<option>');
option.text(this.value).val(customTagPrefix + this.value);
option.attr('selected','selected');
// Append the option an repopulate the chosen field
$('#jform_tags').append(option);
}
}
this.value = '';
$('#jform_tags').trigger('liszt:updated');
event.preventDefault();
}
});
});
})(jQuery); 