// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Bulk Activity Creation
 * @author     Shubhendra R Doiphode <doiphode.sunny@gmail.com>
 * @package block_bulkactivity
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require(['jquery'], function ($) {
    $(document).ready(function () {

        // @var {Object}  The icon configurations.
        var icon = {
            'createactivityincourses': {css: 'editing_backupbulk', pix: 'i/twoway'},
            'backupbulk': {css: 'editing_backupbulk', pix: 'i/twoway'},
        };

        // @var {Node}  The Bulk Activity block container node.
        var $block = $('.block_bulkactivity');

        var $spinner_modal = {
            show: function () {
                $('#bulkactivity-spinner-modal').show();
            },
            hide: function () {
                $('#bulkactivity-spinner-modal').hide();
            }
        };

        /**
         * Returns a localized string
         *
         * @param {String} identifier
         * @return {String}
         */
        function str(identifier) {
            return M.str.block_bulkactivity[identifier] || M.str.moodle[identifier];
        }

        /**
         * Shows an error message with given Ajax error
         *
         * @param {Object} response  The Ajax response
         */
        function show_error(response) {
            try {
                var ex = JSON.parse(response.responseText);
                new M.core.exception({
                    name: str('pluginname') + ' - ' + str('error'),
                    message: ex.message
                });
            } catch (e) {
                new M.core.exception({
                    name: str('pluginname') + ' - ' + str('error'),
                    message: response.responseText
                });
            }
        }

        /**
         * Get an action URL
         *
         * @param {String} name   The action name
         * @param {Object} [args] The action parameters
         * @return {String}
         */
        function get_action_url(name, args) {
            var url = M.cfg.wwwroot + '/blocks/bulkactivity/' + name + '.php';
            if (args) {
                var q = [];
                for (var k in args) {
                    q.push(k + '=' + encodeURIComponent(args[k]));
                }
                url += '?' + q.join('&');
            }
            return url;
        }

        /**
         * Check special layout (theme boost)
         *
         * @return {Boolean}
         */
        function verify_layout() {
            var menuelement = $block.find('.menubar .dropdown .dropdown-menu');
            return (menuelement.length);
        }

        function create_command(name, pix) {
            var imageelement = $('<img class="iconsmall "/>')
                .attr('alt', str(name))
                .attr('src', M.util.image_url(pix || icon[name].pix));
            if (verify_layout()) {
                imageelement.addClass('iconcustom');
            }

            return $('<a href="javascript:void(0)"/>')
                .addClass(icon[name].css)
                .attr('title', str(name))
                .append(imageelement);
        }

        /**
         * Create a command icon for moodle 3.2
         *
         * @param {String} name  The command name, predefined in icon
         * @param {String} [pix] The icon pix name to override
         */
        function create_special_activity_command(name, pix) {
            return $('<a href="javascript:void(0)"/>')
                .addClass(icon[name].css)
                .addClass('dropdown-item menu-action cm-edit-action')
                .attr('title', str(name))
                .append(
                    $('<img class="icon"/>')
                        .attr('alt', str(name))
                        .attr('src', M.util.image_url(pix || icon[name].pix))
                );
        }

        /**
         * Create a spinner
         * @param $node
         * @returns {*|jQuery}
         */
        function add_spinner($node) {
            var WAITICON = {'pix': 'i/loading_small', 'component': 'moodle'};

            if ($node.find('.spinner').length) {
                return $node.find('.spinner');
            }

            var spinner = $('<img/>').attr('src', M.util.image_url(WAITICON.pix, WAITICON.component))
                .addClass('spinner iconsmall')
                .hide();

            $node.append(spinner);
            return spinner;
        }

        function backupbulk(cmid, userdata) {

            window.location.href = M.cfg.wwwroot + '/blocks/bulkactivity/createbulkactivities.php?cba=' + cmid;
        }

        $.get_plugin_name = function () {
            var $blockheader = $block.find('h2');

            if (!$blockheader.length) {
                $blockheader = $block.find('h3');

                if ($blockheader.length) {
                    return $blockheader.html();
                }
            } else {
                return $blockheader.html();
            }

            return '';
        };

        $.on_backupbulk = function (e) {
            var cmid = (function ($backupbulk) {
                var $activity = $backupbulk.closest('li.activity');
                if ($activity.length) {
                    return $activity.attr('id').match(/(\d+)$/)[1];
                }
                var $commands = $backupbulk.closest('.commands');
                var dataowner = $commands.attr('data-owner');
                if (dataowner.length) {
                    return dataowner.match(/(\d+)$/)[1];
                }
                return $commands.find('a.editing_delete').attr('href').match(/delete=(\d+)/)[1];
            })($(e.target));

            (function (on_success) {
                $.post(get_action_url('rest'),
                    {
                        'action': 'is_userdata_copyable',
                        'cmid': cmid
                    },
                    function (response) {
                        on_success(response);
                    }, 'text')
                    .fail(function (response) {
                        show_error(response);
                    });
            })(function (response) {
                function embed_cmid(cmid) {
                    return '<!-- #cmid=' + cmid + ' -->';
                }

                function parse_cmid(question) {
                    return /#cmid=(\d+)/.exec(question)[1];
                }

                var copyable = response === '1';
                if (copyable) {
                    if (confirm(str('confirm_userdata'))) {
                        if (confirm(str('confirm_copy'))) {
                            backupbulk(cmid, true);
                        }
                    } else {
                        if (confirm(str('confirm_copy'))) {
                            backupbulk(cmid, false);
                        }
                    }
                } else {
                    if (confirm(str('confirm_copy'))) {
                        backupbulk(cmid, false);
                    }
                }
            });
        };

        $.init_activity_commands = function () {
            function add_backupbulk_comand($activity) {
                var $menu = $activity.find('ul[role=\'menu\']');

                if ($menu.length) {
                    var li = $menu.find('li').first().clone();
                    var $backupbulk = li.find('a').attr('title', str('createactivityincourses', 'block_bulkactivity')).attr('href', 'javascript:void(0)');
                    var img = li.find('img');

                    if (img.length) {
                        li.find('img').attr('alt', str('createactivityincourses', 'block_bulkactivity')).attr('title', str('createactivityincourses', 'block_bulkactivity')).attr('src', M.util.image_url(icon.backupbulk.pix));
                    } else {
                        li.find('i').attr('class', 'icon fa fa-upload').attr('title', str('createactivityincourses', 'block_bulkactivity')).attr('aria-label', str('createactivityincourses', 'block_bulkactivity'));
                    }

                    li.find('span').html('Create Bulk Activity');
                    $menu.append(li);

                } else {
                    var $backupbulk = create_command('createactivityincourses');
                    $menu = $activity.find('div[role="menu"]');
                    if ($menu.length) {
                        $backupbulk = create_special_activity_command('createactivityincourses');
                        $menu.append($backupbulk.attr('role', 'menuitem'));
                        if ($menu.css('display') === 'none') {
                            $backupbulk.append($('<span class=\'menu-action-text\'/>').append($backupbulk.attr('title')));
                        }

                    } else {
                        $activity.find('.commands').append($backupbulk);
                    }
                }

                $backupbulk.click(function (e) {
                    $.on_backupbulk(e);
                });
            }

            // if (course.is_frontpage) {
                $('.sitetopic li.activity').each(function () {
                    add_backupbulk_comand($(this));
                });
                $('.block_site_main_menu .content > ul > li').each(function () {
                    add_backupbulk_comand($(this));
                });
            // } else {
                $('.course-content li.activity').each(function () {
                    add_backupbulk_comand($(this));
                });
            // }
        };

        /**
         * Initialize the Bulk Activity Block
         */
        $.init = function () {
            M.str.block_bulkactivity.pluginname = this.get_plugin_name();

            $.init_activity_commands();
        };

        var WAITICON = {'pix': 'i/loading', 'component': 'moodle'};
        var $spinner = $('<img/>').attr('src', M.util.image_url(WAITICON.pix, WAITICON.component)).addClass('spinner');
        $('div#bulkactivity-spinner-modal div.spinner-container').prepend($spinner);

        $.init();

    });

});
