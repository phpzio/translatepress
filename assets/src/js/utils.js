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

function getFilename( url ) {
    if ( url )
        return url.substring( url.lastIndexOf( "/" ) + 1, url.lastIndexOf( "." ) )

    return url
}

function unwrap( wrapper ) {
    let docFrag = document.createDocumentFragment();

    while (wrapper.firstChild) {
        let child = wrapper.removeChild( wrapper.firstChild );
        docFrag.appendChild( child );
    }

    wrapper.parentNode.replaceChild( docFrag, wrapper );
}

function arrayContainsItem( array, item ){
    let i
    let length = array.length
    for ( i = length -1; i >= 0; i-- ){
        if ( array[i] === item ){
            return true
        }
    }
    return false
}

export default {
    removeUrlParameter,
    escapeHtml,
    getFilename,
    arrayContainsItem,
    unwrap
}
