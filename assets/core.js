'use strict';

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

        var $sortable = $("#sitmd-sortable-js"),
            $template = $($("#sitmd-sortable-item-tpl-js").prop( "content" )).clone();

        $sortable.append( $template );

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
            return this.value;
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

    }

}

$(document).ready(function () {
    new SITMDCore();
});
