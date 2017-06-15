function confirmBackspaceNavigations(){
    "use strict";
    var lastUserInputWasBackspace = false;
    $(document).keydown(function(event){
        lastUserInputWasBackspace = event.which == 8 ? true : false;
    });
    $(document).mousedown(function(){
        lastUserInputWasBackspace = false;
    });
    $(window).on('beforeunload', function(){
        if (lastUserInputWasBackspace) {
            return "Are you sure you want to leave this page?";
        }
    });
}

confirmBackspaceNavigations();

function pad(width, string, padding) {
  return (width <= string.length) ? string : pad(width, padding + string, padding);
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
        stopLogoutTimer();
    });
}

function spawnExpirationNotice() {
    startLogoutTimer();

    message = 'SESSION EXPIRATION NOTIFICATION';
    message += "\n\n" + 'WARNING! YOUR SESSION WILL EXPIRE IN 5 MINUTES AND YOU WILL BE LOGGED OUT!';
    message += "\n\n" + 'Click "Ok" to stay logged in or "Cancel" to be logged out immediately.';

    confirmed = window.confirm(message);

    if (confirmed) {
        refreshSession();
    } else {
        logOut();
    }
}

function sessionIsExpiring() {
    "use strict";
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

jQuery(document).ready(function() {
    //Increment the idle time counter every minute.
    idleTimer = setInterval(updateIdleTime, 60000);

    //Zero the idle timer on mouse movement.
    jQuery(document).on('mousemove',function (e) {
        idleTime = 0;
    });

    jQuery(document).on('keypress',function (e) {
        idleTime = 0;
    });

    jQuery('#sessionExpire').on('click', function(e){
        // log the user out
        window.location.href = 'index.php?action=logout';
    });

   jQuery('#sessionRefresh').on('click', function(e){
        refreshSession();
    });
});
