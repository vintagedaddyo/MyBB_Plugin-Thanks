/*
* MyBB: Thanks
*
* File: thx.js
*
* Authors: Huji Lee, AliReza Tofighi, SaeedGh, Vintagedaddyo, effone
*
* MyBB Version: 1.8
*
* Plugin Version: 3.9.4
*
*/

var pid = -1;
function thx_common(action, xml) {
    try {
        can_remove = $(xml).find('del').text() == "1";
        pid = $(xml).find('pid').text();
        thxbtn = $('.thx_btn_wait[data-thx="' + pid + '"]');
        thxbtn.removeClass().addClass($(xml).find('btnclass').text()).html('<span>' + $(xml).find('btntext').text() + '</span>');

        if ($(xml).find('display').text() != 0) {
            $('#thx' + pid).show();
        } else {
            $('#thx' + pid).hide();
        }

        if ($(xml).find('display').text() == '1') {
            $('#thx_list' + pid).removeClass("hide").show();
            $('#thx_entry' + pid).html($(xml).find('list').text());
        } else {
            $('#thx_list' + pid).hide();
        }

        if (!can_remove) {
            thxbtn.hide();
        }
    } catch (err) {
        alert("an Error had occured please contact administrator")
        alert(err);
    } finally {
        return thxbtn;
    }
}


function thanks(id, act) {
    if (act == "add") {
        actParam = "";
        actRev = "remove_";
    } else if (act == "remove") {
        actRev = "";
        actParam = "remove_";
    } else {
        return false;
    }

    $.ajax({
        url: "xmlhttp.php?action=" + actParam + "thankyou",
        type: 'POST',
        data: "pid=" + parseInt(id),
        dataType: 'XML',
        success: function (response) {
            if (thx_common(act, response) != null) {
                thxbtn.attr('href', 'showthread.php?action=' + actRev + 'thank&pid=' + pid);
            }
        }
    });
    return false;
}

$(function () {
    lang.processing = lang.processing || 'Processing...';

    $("a[class^='thx_btn_']").on('click', function (e) {
        e.stopImmediatePropagation();
        e.preventDefault();
        var preClass = $(this).attr('class');
        $(this).removeClass(preClass).addClass('thx_btn_wait').find('span').text(lang.processing);
        return thanks($(this).data('thx'), preClass.replace('thx_btn_', ''));
    });
});
