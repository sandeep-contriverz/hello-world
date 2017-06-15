function confirmBackspaceNavigations(){
    var lastUserInputWasBackspace = false
    $(document).keydown(function(event){
        lastUserInputWasBackspace = event.which == 8 ? true : false
    })
    $(document).mousedown(function(){
        lastUserInputWasBackspace = false
    })
    $(window).on('beforeunload', function(){
        if (lastUserInputWasBackspace) {
            return "Are you sure you want to leave this page?"
        }
    })
}

confirmBackspaceNavigations();

function pad(width, string, padding) {
  return (width <= string.length) ? string : pad(width, padding + string, padding)
}

var logoutTimer;
var idleTimer;
var idleTime = 0;

function updateIdleTime() {
    idleTime = idleTime + 1;
    if (idleTime >= 25) {
        spawnExpirationNotice();
    } else if (sessionIsExpiring()) {
       refreshSession();
    }
}

function logOut() {
  window.location.href = 'index.php?action=logout';
}

function startLogoutTimer() {
    logoutTimer = setTimeout(logOut, 60000);
}

function stopLogoutTimer() {
    window.clearTimeout(logoutTimer);
}

function refreshSession() {
    $.get('index.php?action=refresh-session', function(data){
        expireTime = data.expireTime;
        $.fancybox.close();
        stopLogoutTimer();
    });
}

function spawnExpirationNotice() {
    startLogoutTimer();
    $('#confirmation').fancybox({
        padding: '0px',
        modal: true
    }).click();
}

function sessionIsExpiring() {
    var isExpiring = false;
    var expireSeconds = expireTime;
    var date = new Date();
    var nowSeconds = Math.round(date.getTime() / 1000);
    var diffSeconds = expireSeconds - nowSeconds;
    if (diffSeconds <= 300) {
        isExpiring = true;
    }

    return isExpiring;
}

$(document).ready(function() {
    //Increment the idle time counter every minute.
    idleTimer = setInterval(updateIdleTime, 60000); // 1 minute

    //Zero the idle timer on mouse movement.
    $(this).mousemove(function (e) {
        idleTime = 0;
    });

    $(this).keypress(function (e) {
        idleTime = 0;
    });

    $('#sessionExpire').on('click', function(e){
        // log the user out
        window.location.href = 'index.php?action=logout';
    });

    $('#sessionRefresh').on('click', function(e){
        refreshSession();
    });
});
