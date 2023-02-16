/**
 * DragHandler library for use with prototypejs.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2013-2015 Horde LLC
 * @license    GPL-2 (http://www.horde.org/licenses/gpl)
 */

var DragHandler = {

    // dropelt,
    // droptarget,
    // hoverclass,
    // leave,

    to: -1,

    isFileDrag: function(e)
    {
        //console.log(e);
        console.log("start");
        console.log(e);
        let first = e.dataTransfer;
        let second = e.dataTransfer.types;
        let third = $A(e.dataTransfer.types).include('Files');
        let fourth = ((e.type != 'drop') || e.dataTransfer.files.length);
        console.log(first);
        console.log(second);
        console.log(third);
        console.log(fourth);
        console.log("stop");
        e.dataTransfer.clearData();
        let toReturn = first && second && third && fourth;
        return toReturn;
        // return (e.dataTransfer &&
        //         e.dataTransfer.types &&
        //         $A(e.dataTransfer.types).include('Files') &&
        //         ((e.type != 'drop') || e.dataTransfer.files.length));
    },

    handleObserve: function(e)
    {
        if (this.dropelt &&
            (e.dataTransfer ||
             (e.memo && e.memo.dataTransfer) ||
             this.dropelt.visible())) {
            if (Prototype.Browser.IE &&
                !(("onpropertychange" in document) && (!!window.matchMedia))) {
                // IE 9 supports drag/drop, but not dataTransfer.files
                console.log('holy cow');
            } else {
                switch (e.type) {
                case 'dragleave':
                    this.handleLeave();
                    break;

                case 'dragover':
                    this.handleOver(e);
                    break;

                case 'drop':
                    this.handleDrop(e);
                    break;
                }
            }
        }
    },

    handleDrop: function(e)
    {
        this.leave = true;
        this.hide();

        if (this.isFileDrag(e)) {
            if (this.dropelt.hasClassName(this.hoverclass)) {
                this.dropelt.fire('DragHandler:drop', e.dataTransfer.files);
            }
            e.stop();
        } else if (!e.findElement('TEXTAREA') && !e.findElement('INPUT')) {
            e.stop();
        }
    },

    hide: function()
    {
        if (this.leave) {
            this.dropelt.hide();
            this.droptarget.show();
            this.leave = false;
        }
    },

    handleLeave: function()
    {
        clearTimeout(this.to);
        this.to = this.hide.bind(this).delay(0.25);
        this.leave = true;
    },

    handleOver: function(e)
    {
        console.log('test0 handleOver');
        var file = this.isFileDrag(e);
        console.log('test1 handleOver');
        //console.log(this.dropelt.visible());
        console.log(file);

        if (file && !this.dropelt.visible()) {
            console.log('this should trigger');
            this.dropelt.clonePosition(this.droptarget).show();
            this.droptarget.hide();
        }
        console.log('test2 handleOver');
        this.leave = false;

        if (file && (e.target == this.dropelt)) {
            this.dropelt.addClassName(this.hoverclass);
            e.stop();
        } else {
            this.dropelt.removeClassName(this.hoverclass);
            if (Prototype.Browser.IE ||
                Prototype.Browser.Gecko) {
                e.stop();
            }
        }
    }

};

document.observe('dragleave', DragHandler.handleObserve.bindAsEventListener(DragHandler));
document.observe('dragover', DragHandler.handleObserve.bindAsEventListener(DragHandler));
document.observe('drop', DragHandler.handleObserve.bindAsEventListener(DragHandler));
