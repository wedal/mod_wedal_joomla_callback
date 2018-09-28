jQuery(document).ready(function() {

    var $firstname;
    var $lastname;
    var $secondname;
    var $fio;
    var $nums = jQuery('.numerology #wnum-nums').text();
    $nums = jQuery.parseJSON($nums);

    jQuery('.numerology').on('click', '.submit', function(event) {
        event.preventDefault();
        $firstname = jQuery('.numerology #firstname').val();
        $lastname = jQuery('.numerology #lastname').val();
        $middlename = jQuery('.numerology #middlename').val();

        $fio = $firstname+$lastname+$middlename;
        $fio = wnumcalc($fio);
        jQuery('#numerology-result').load('index.php?option=com_content&view=article&tmpl=component&id='+$nums[$fio]);
    });

});

function wnumcalc($fio) {
    var $wnumbase = {
         "а":"1", "б":"2", "в":"3", "г":"4", "д":"5", "е":"6", "ё":"7", "ж":"8", "з":"9",
         "и":"1", "й":"2", "к":"3", "л":"4", "м":"5", "н":"6", "о":"7", "п":"8", "р":"9",
         "с":"1", "т":"2", "у":"3", "ф":"4", "х":"5", "ц":"6", "ч":"7", "ш":"8", "щ":"9",
         "ъ":"1", "ы":"2", "ь":"3", "э":"4", "ю":"5", "я":"6"}

    $fio =  $fio.replace(/[^А-Яа-яЁё]/gi,'').replace(/\s+/gi,', ').toLowerCase();
    var $symbols = $fio.split("");

    var $sum = 0;
    $symbols.forEach(function(item, i, symbols) { // в цикле по всем
        $sum += Number($wnumbase[item]);
    });

    if ($sum > 9) {
        $sum = sumrecurse($sum);
    }
    return $sum;
}

function sumrecurse($sum) {
    var $num = 0, tmp;
    while ($sum) {
        $tmp = $sum % 10;
        $sum = ($sum - $tmp) / 10;
        $num += $tmp;
    }

    if ($num > 9) {
        $num = sumrecurse($num);
    }
    return $num;
}
