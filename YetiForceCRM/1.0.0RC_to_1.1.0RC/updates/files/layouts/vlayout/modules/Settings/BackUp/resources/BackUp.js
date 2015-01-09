/*+***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 *************************************************************************************************************************************/

var Settings_BackUp_Js = {
    registerCreateBackUpEvent: function () {
        jQuery('.saveBackUp').on('click', function (e) {
            //jQuery('.saveBackUp').addClass('disabled');
            jQuery('#backup-progress-bar').show();
            jQuery('.backup-db-prepare').show();
            jQuery('.backup-ended').hide();
            jQuery('#resumeBackup').hide();
			jQuery('.backup-files-loading').hide();
            Settings_BackUp_Js.registerBackUpEvent('new');
            e.preventDefault();
            return;
        });
    },
    registerResumeBackUpEvent: function () {
        jQuery('#resumeBackup').on('click', function (e) {
            jQuery('.saveBackUp').addClass('disabled');
            jQuery('#backup-progress-bar').show();
            jQuery('#resumeBackup').hide();
            Settings_BackUp_Js.registerBackUpEvent('resume');
            e.preventDefault();
            return;
        });
    },
    registerNextPagePaginationEvent: function () {
        jQuery('#listViewNextPageButton').on('click', function (e) {
            var ftpButtonTitle = jQuery('.ftp-button').attr('title');
            var params = {};
            var offset = $('.offset').val();
            var page = parseInt($('.current-page').val()) + 1;
            params.data = {
                module: 'BackUp',
                action: 'Pagination',
                offset: offset,
                page: page,
                ajaxCall: true,
                parent: app.getParentModuleName()

            };
            params.dataType = 'json';
            AppConnector.request(params).then(function (data) {
                var response = data['result'];
                if (data.success == true) {
                    Settings_BackUp_Js.registerUpdateBackUpList(data);
                }
            });
            e.preventDefault();
            return;
        });
    },
    registerPrevPagePaginationEvent: function () {
        jQuery('#listViewPreviousPageButton').on('click', function (e) {
            var params = {};
            var offset = $('.offset').val();
            var page = parseInt($('.current-page').val()) - 1;
            params.data = {
                module: 'BackUp',
                action: 'Pagination',
                offset: offset,
                page: page,
                ajaxCall: true,
                parent: app.getParentModuleName()
            };
            params.dataType = 'json';
            AppConnector.request(params).then(function (data) {
                var response = data['result'];
                if (data.success == true) {
                    Settings_BackUp_Js.registerUpdateBackUpList(data);
                }
            });
            e.preventDefault();
            return;
        });
    },
    registerSetPaginationNavigation: function () {
        var nextPage = $('.next-page').val();
        var pregPage = $('.prev-page').val();
        if (nextPage == true) {
            $('#listViewNextPageButton').prop('disabled', false);
        } else {
            $('#listViewNextPageButton').prop('disabled', true);
        }
        if (pregPage == true) {
            $('#listViewPreviousPageButton').prop('disabled', false);
        } else {
            $('#listViewPreviousPageButton').prop('disabled', true);
        }

    },
    registerBackUpEvent: function (backUpAction) {
        var params = {};
        params.data = {
            module: 'BackUp',
            action: 'CreateBackUp',
            parent: app.getParentModuleName(),
            backUpAction: backUpAction
        };
        params.dataType = 'json';

        AppConnector.request(params).then(function (data) {
            if (data.status == 'pending') {
                Settings_BackUp_Js.registerUpdateDBBackUpProgressBarEvent(data.percentage);
                Settings_BackUp_Js.registerBackUpEvent();
                jQuery('.backup-db-prepare').hide();
                jQuery('.backup-db-loading').show();
            }
            if (data.status == 'prepare') {
                jQuery('.backup-db-prepare').show();
                Settings_BackUp_Js.registerUpdateDBBackUpProgressBarEvent(data.percentage);
                Settings_BackUp_Js.registerBackUpEvent();
            }
            if (data.status == 'end') {
                Settings_BackUp_Js.registerUpdateDBBackUpProgressBarEvent(0);
                jQuery('.backup-db-loading').hide();
                jQuery('.backup-db-prepare').hide();
                jQuery('.backup-files-loading').show();
                Settings_BackUp_Js.registerAppConectorFileBacUpEvent();
            }

        });
    },
    registerUpdateDBBackUpProgressBarEvent: function (percent) {
        jQuery('.bar-backup').css('width', percent + '%');
    },
    registerCreateFileBackUpAction: function () {
        jQuery('#saveFileBackUp').on('click', function (e) {
            jQuery('#loading').show();
            jQuery('#backup-file-progress-bar').show();
            setTimeout(function () {
                Settings_BackUp_Js.registerAppConectorFileBacUpEvent();
            }, 1250);
            e.preventDefault();
            return;
        });
    },
    registerAppConectorFileBacUpEvent: function () {
        var params = {};
        params.data = {
            module: 'BackUp',
            action: 'CreateFileBackUp',
            parent: app.getParentModuleName()
        };
        params.dataType = 'json';
        AppConnector.request(params).then(function (data) {
            var response = data['result'];
            var percentage = data['percentage'];
            if (typeof (percentage) !== 'undefined') {
                Settings_BackUp_Js.registerUpdateDBBackUpProgressBarEvent(percentage);
                if (percentage < 100) {
                    Settings_BackUp_Js.registerAppConectorFileBacUpEvent();
                } else {
                    jQuery('.saveBackUp').removeClass('disabled');
                    $('#resumeBackup').hide();
                    jQuery('.backup-files-loading').hide();
                    jQuery('.backup-ended').show();
                    var params = {};
                    params.data = {
                        module: 'BackUp',
                        action: 'Pagination',
                        ajaxCall: true,
                        parent: app.getParentModuleName()

                    };
                    params.dataType = 'json';
                    AppConnector.request(params).then(function (data) {
                        var response = data['result'];
                        if (data.success == true) {
                            Settings_BackUp_Js.registerUpdateBackUpList(data)
                        }
                    });
                }
            }

        });
    },
    registerUpdateBackUpList: function (data) {
        jQuery('.offset').val(data['result'].offset);
        jQuery('.current-page').val(data['result'].page);
        $('.next-page').val(data['result'].nextPage);
        $('.prev-page').val(data['result'].prevPage);
        Settings_BackUp_Js.registerSetPaginationNavigation();
        var ftpButtonTitle = jQuery('.ftp-button').attr('title');

        jQuery('.backup-list').empty();
        jQuery(data['result'].backups).each(function () {
            jQuery('.backup-list').append('<tr><td><label class="marginRight5px" >' + this.created_at + '</label></td><td><label class="marginRight5px" >' + this.file_name + '</label></td></tr>')
        });

    },
    registerSendBackUpFileOnFtp: function () {
        var params = {};
        params.data = {
            module: 'BackUp',
            action: 'SendBackUpOnFtp',
            parent: app.getParentModuleName()
        };
        params.async = false;
        params.dataType = 'json';
        AppConnector.request(params).then(function (data) {
            var response = data['result'];
        });
    },
    registerSaveFTPConfigEvent: function () {
        jQuery('#saveConfig').on('click', function (e) {
            var ftpHost = jQuery('[name="ftpservername"]').val();
            var ftpLogin = jQuery('[name="ftplogin"]').val();
            var ftpPassword = jQuery('[name="ftppassword"]').val();
            var params = {};
            params.data = {
                module: 'BackUp',
                action: 'SaveFTPConfig',
                ftpservername: ftpHost,
                ftplogin: ftpLogin,
                ftppassword: ftpPassword,
                parent: app.getParentModuleName()
            };
            params.dataType = 'json';
            AppConnector.request(params).then(function (data) {
                var response = data['result'];
                if (response.fptConnection == true) {
                    $('#connection-status').css('background-color', '#5bb75b');
                } else {
                    $('#connection-status').css('background-color', 'red');
                }
            });
            e.preventDefault();
            return;
        });
    },
    registerEvents: function () {
        Settings_BackUp_Js.registerSaveFTPConfigEvent();
        Settings_BackUp_Js.registerCreateBackUpEvent();
        Settings_BackUp_Js.registerCreateFileBackUpAction();
        Settings_BackUp_Js.registerSetPaginationNavigation();
        Settings_BackUp_Js.registerNextPagePaginationEvent();
        Settings_BackUp_Js.registerResumeBackUpEvent();
        Settings_BackUp_Js.registerPrevPagePaginationEvent();

        $("#backup_tab_btn_1").click(function () {
            $("#backup_tab_btn_2").attr('class', '');
            $("#backup_tab_btn_1").attr('class', 'active');
            $("#backup_tab_2").hide();
            $("#backup_tab_1").show();
        });
        $("#backup_tab_btn_2").click(function () {
            $("#backup_tab_btn_1").attr('class', '');
            $("#backup_tab_btn_2").attr('class', 'active');
            $("#backup_tab_1").hide();
            $("#backup_tab_2").show();
        });
    }
};
jQuery(document).ready(function () {
    Settings_BackUp_Js.registerEvents();
});