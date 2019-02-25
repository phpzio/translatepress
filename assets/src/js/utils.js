function removeUrlParameter( url, parameter ) {

    let parts = url.split( '?' )

    if ( parts.length >= 2 ) {

        let prefix = encodeURIComponent( parameter ) + '='
        let pairs = parts[1].split( /[&;]/g )

        //reverse iteration as may be destructive
        for ( let i = pairs.length; i-- > 0; ) {
            //idiom for string.startsWith
            if ( pairs[i].lastIndexOf(prefix, 0) !== -1 ) {
                pairs.splice(i, 1)
            }
        }

        url = parts[0] + ( pairs.length > 0 ? '?' + pairs.join('&') : "" )

        return url

    } else {
        return url
    }
}

function escapeHtml( string ){
    let escape = document.createElement('textarea');
    escape.textContent = string;
    return escape.innerHTML;
}

export default {
    removeUrlParameter,
    escapeHtml
}
