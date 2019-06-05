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
    let doc = new DOMParser().parseFromString( string, 'text/html' )

    return doc.body.textContent || ""
}


function getFilename( url ){
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

//Adds or updates an existing query parameter in an url
function updateUrlParameter(uri, key, value) {
    let regex = new RegExp("([?&])" + key + "=.*?(&|#|$)", "i")

    if ( uri.match(regex) )
        return uri.replace(regex, '$1' + key + "=" + value + '$2')
    else {
        let hash = ''

        if( uri.indexOf('#') !== -1 ){
            hash = uri.replace(/.*#/, '#')
            uri = uri.replace(/#.*/, '')
        }

        let separator = uri.indexOf('?') !== -1 ? "&" : "?"

        return uri + separator + key + "=" + value + hash
    }
}

//Given an arbitrary URL, returns an array with the URL parameters
function getUrlParameters( url ){
    let query = url.split('?')

    if( !query[1] )
        return null

    let vars = query[1].split('&'), query_string = {}, i

    for ( i = 0; i < vars.length; i++ ) {
        let pair  = vars[i].split('='),
            key   = decodeURIComponent(pair[0]),
            value = decodeURIComponent(pair[1])

        if ( typeof query_string[key] === 'undefined' )
            query_string[key] = decodeURIComponent(value)
        else if ( typeof query_string[key] === 'undefined' )
            query_string[key] = [ query_string[key], decodeURIComponent(value) ]
        else
            query_string[key].push(decodeURIComponent(value) )
    }

    return query_string
}

export default {
    removeUrlParameter,
    updateUrlParameter,
    getUrlParameters,
    escapeHtml,
    getFilename,
    arrayContainsItem,
    unwrap
}
