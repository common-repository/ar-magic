(function() {
    tinymce.create('tinymce.plugins.ar_magic', {
        init : function(ed, url) {
            ed.addCommand('ar_magic', function() {
                ed.windowManager.open({
                    file : ajaxurl + '?action=armagic_shortpop',
                    width : 480 + parseInt(ed.getLang('example.delta_width', 0)),
                    height : 550 + parseInt(ed.getLang('example.delta_height', 0)),
                    inline : 1
                }, {
                    plugin_url : url
                });
            });

            ed.addButton('ar_magic', {
                title : 'AR Magic',
                image : url+'/arMagic_logo128.png',
                cmd : 'ar_magic'
            });
        },
        createControl : function(n, cm) {
            return null;
        },
        getInfo : function() {
            return {
                longname : "AR Magic",
                author : 'BZ9',
                authorurl : 'http://bz9.com',
                infourl : 'http://bz9.com'
            };
        }
    });
    tinymce.PluginManager.add('ar_magic', tinymce.plugins.ar_magic);
})();