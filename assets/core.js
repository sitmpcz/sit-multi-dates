'use strict';

(function ($) {

    function SITMDCore() {

        var self = this;

        self.$window = $(window);
        self.$document = $(document);
        self.$body = $("body");
        self.$hiddenField = $("#sitmd-other-js");

        self.init();

    }

    SITMDCore.prototype = {

        init: function () {

            var self = this;

            self.bind();
            self.sortable();

        },

        bind: function() {

            var self = this;

            $("#sitmd-add-js").on( "click", function() {
                self.add();
            } );

            self.$body.on( "change", ".sitmd-other-dates-js", function() {
                self.changeDate( $(this) );
            } );

            self.$body.on( "click", ".sitmd-remove-js", function() {
                self.remove( $(this) );
            } );

        },

        add: function() {

            var self = this;

            var $sortable = $("#sitmd-sortable-js"),
                $template = $($("#sitmd-sortable-item-tpl-js").prop( "content" )).clone();

            $sortable.append( $template );

            self.changeDate( $template.find(".sitmd-other-dates-js") );

        },

        remove: function( $this ) {

            var self = this;

            $this.closest(".sitmd-sortable-item-js").remove();

            self.makeValue();

        },

        changeDate: function( $this ) {

            var self = this;

            self.checkDuplicate( $this.val() );
            self.makeValue();
        },

        makeValue: function() {

            var self = this;

            var values = $(".sitmd-other-dates-js").map( function() {
                return self.parseAndFormatDate( this.value );
                //return self.convertToDateTimeLocalString(this.value);
                //return this.value;
            } ).get();

            self.$hiddenField.val( values.join(",") );

        },

        checkDuplicate: function( value ) {

            var self = this;

            var v = self.$hiddenField.val(),
                values = v.split( ',');

            if ( typeof value !== undefined && values.indexOf( value ) >= 0 ) {
                alert("Takové datum tam už máme, zkuste jej změnit.");
            }

        },

        sortable: function () {

            var self = this;

            self.sortable = new Sortable( $("#sitmd-sortable-js")[0], {
                filter: ".sitmd-sortable-filter-js",
                onSort: function() {
                    self.makeValue();
                }
            } );

        },

        parseAndFormatDate: function( dateString ) {

            const date = new Date(Date.parse( dateString ));

            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            const seconds = String(date.getSeconds()).padStart(2, '0');

            return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;

        },

        convertToDateTimeLocalString: function(date) {

            const year = date.getFullYear();
            const month = (date.getMonth() + 1).toString().padStart(2, "0");
            const day = date.getDate().toString().padStart(2, "0");
            const hours = date.getHours().toString().padStart(2, "0");
            const minutes = date.getMinutes().toString().padStart(2, "0");

            return `${year}-${month}-${day}T${hours}:${minutes}`;

        }

    }

    $(document).ready(function () {
        new SITMDCore();
    });

})(jQuery);
