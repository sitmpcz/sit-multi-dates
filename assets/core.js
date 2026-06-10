'use strict';

class SITMDCore {

    constructor() {

        this.hiddenField = document.getElementById( "sitmd-other-js" );
        this.sortableEl = document.getElementById( "sitmd-sortable-js" );

        // Metabox na strance neni - nic nedelame
        if ( !this.sortableEl || !this.hiddenField ) {
            return;
        }

        this.init();

    }

    init() {

        this.bind();
        this.sortable();

    }

    bind() {

        const addBtn = document.getElementById( "sitmd-add-js" );
        if ( addBtn ) {
            addBtn.addEventListener( "click", () => this.add() );
        }

        // Delegace - radky se pridavaji dynamicky
        document.addEventListener( "change", ( e ) => {
            if ( e.target.classList.contains( "sitmd-other-dates-js" ) ) {
                this.changeDate( e.target );
            } else if ( e.target.classList.contains( "sitmd-duration-js" ) ) {
                this.makeValue();
            }
        } );

        document.addEventListener( "click", ( e ) => {
            const removeBtn = e.target.closest( ".sitmd-remove-js" );
            if ( removeBtn ) {
                this.remove( removeBtn );
            }
        } );

    }

    add() {

        const tpl = document.getElementById( "sitmd-sortable-item-tpl-js" );
        const clone = document.importNode( tpl.content, true );
        const dateInput = clone.querySelector( ".sitmd-other-dates-js" );

        this.sortableEl.appendChild( clone );

        this.changeDate( dateInput );

    }

    remove( el ) {

        const item = el.closest( ".sitmd-sortable-item-js" );
        if ( item ) {
            item.remove();
        }

        this.makeValue();

    }

    changeDate( el ) {

        this.checkDuplicate( el.value );
        this.makeValue();

    }

    makeValue() {

        // Kazdy radek = datum + delka v hodinach, serializujeme jako "datum|hodiny"
        const values = [];
        const items = document.querySelectorAll( ".sitmd-sortable-item-js" );

        items.forEach( ( item ) => {
            const dateEl = item.querySelector( ".sitmd-other-dates-js" );
            const durationEl = item.querySelector( ".sitmd-duration-js" );

            const date = dateEl ? dateEl.value : "";
            if ( !date ) {
                return;
            }

            const hours = durationEl ? durationEl.value : "";
            const h = ( hours === "" || hours === undefined ) ? "0" : hours;

            values.push( this.parseAndFormatDate( date ) + "|" + h );
        } );

        this.hiddenField.value = values.join( "," );

    }

    checkDuplicate( value ) {

        // Polozky jsou "datum|hodiny", pro kontrolu duplicit nas zajima jen datum
        const values = this.hiddenField.value.split( "," ).map( ( item ) => item.split( "|" )[0] );

        if ( typeof value !== undefined && values.indexOf( this.parseAndFormatDate( value ) ) !== -1 ) {
            alert( "Takové datum tam už máme, zkuste jej změnit." );
        }

    }

    sortable() {

        this.sortableInstance = new Sortable( this.sortableEl, {
            filter: ".sitmd-sortable-filter-js",
            onSort: () => this.makeValue()
        } );

    }

    parseAndFormatDate( dateString ) {

        const date = new Date( Date.parse( dateString ) );

        const year = date.getFullYear();
        const month = String( date.getMonth() + 1 ).padStart( 2, '0' );
        const day = String( date.getDate() ).padStart( 2, '0' );
        const hours = String( date.getHours() ).padStart( 2, '0' );
        const minutes = String( date.getMinutes() ).padStart( 2, '0' );
        const seconds = String( date.getSeconds() ).padStart( 2, '0' );

        return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;

    }

}

new SITMDCore();
