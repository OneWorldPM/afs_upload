<title>Admin - AFS Congress Presentations</title>

<main role="main" style="margin-top: 70px;margin-left: 20px;margin-right: 20px;">
    <div class="row">
        <div class="col-md-12">
            <h3>Presentations</h3>
            <p>Loaded presentations are listed here</p>

            <div id="lastUpdatedAlert" class="alert alert-warning alert-dismissible fade show" role="alert" style="display:none;">
                This list was last loaded on <strong><span id="lastUpdated"></span></strong>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

        </div>
        <a href="<?=base_url().'admin/dashboard/presentationToCsv'?>" target="_blank" class="btn btn-primary float-left mb-2 ml-5 text-white" style="cursor: pointer"><i class="fas fa-file-csv"></i> Export CSV</a>
        <div class="col-md-12">
            <button class="create-presentation-btn btn btn-success float-right"><i class="fas fa-plus"></i> Create</button>
            <table id="presentationTable" class="table table-striped table-bordered" style="width:100%">
                <thead>
                <tr>
                    <th>Status</th>
                    <th>ID</th>
                    <th style=" white-space: nowrap "> Session Time</th>
                    <th>Presentation Start</th>
                    <th>Room</th>
                    <th>Session Name</th>
                    <th>Presentation Title</th>
                    <th>Presenter</th>
                    <th>Email</th>
                    <th>Info</th>
                    <th>Actions</th>
                </tr>
                </thead>

                <tbody id="presentationTableBody">
                <!-- Will be filled by JQuery AJAX -->
                </tbody>

            </table>
        </div>

    </div>

    <hr>
</main>

<script src="https://cdn.datatables.net/1.10.23/js/jquery.dataTables.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/1.10.23/js/dataTables.bootstrap4.min.js" crossorigin="anonymous"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.23/css/dataTables.bootstrap4.min.css" crossorigin="anonymous" />


<script>
    $(document).ready(function() {

        loadPresentations();

        $('#example-upload-btn').on('click', function () {
            toastr.warning('You need to click one of the similar buttons listed below to upload files.');
        });

        $('.change-pass-btn').on('click', function () {
            $('#changePasswordModal').modal('show');
        });

        $('#presentationTable').on('click', '.files-btn', function () {

            let user_id = $(this).attr('user-id');
            let presentation_id = $(this).attr('presentation-id');
            let presentation_name = $(this).attr('presentation-name');
            let session_name = $(this).attr('session-name');
            let room_id = $(this).attr('room_id');
            let room_name = $(this).attr('room_name');
            let presentation_date = $(this).attr('presentation_date');

            showFiles(user_id, presentation_id, session_name, presentation_name, room_id, room_name, presentation_date);
        });

        $('#presentationTable').on('click', '.details-btn', function () {

            let user_id = $(this).attr('user-id');
            let presentation_id = $(this).attr('presentation-id');
            let presentation_name = $(this).attr('presentation-name');
            let session_name = $(this).attr('session-name');
            let room_id = $(this).attr('room_id');
            let room_name = $(this).attr('room_name');
            let presentation_date = $(this).attr('presentation_date');

            showUploader(user_id, presentation_id, session_name, presentation_name, room_id, room_name, presentation_date);
        });

        $('#presentationTable').on('click', '.activate-presentation-btn', function () {
            let button = $(this);
            let presentationId = $(this).attr('presentation-id');

            activatePresentation(presentationId, button);
        });

        $('#presentationTable').on('click', '.disable-presentation-btn', function () {
            let button = $(this);
            let presentationId = $(this).attr('presentation-id');

            disablePresentation(presentationId, button);
        });

        $('#presentationTable').on('click', '.presentation-logs-btn', function () {
            toastr.warning("Under development");
        });

        $('#presentationTable').on('click', '.edit-presentation-btn', function () {
            let button = $(this);
            let presentationId = $(this).attr('presentation-id');
            let upload_status = $(this).attr('upload-status');

            edit_presentation(presentationId, upload_status);
        });

        $('.create-presentation-btn').on('click', function (e) {
            e.preventDefault();
            $('#createPresentationModal').modal('show');
        });

    } );



    function loadPresentations() {
        $.get( "<?=base_url('admin/dashboard/getPresentationList')?>", function(response) {
            response = JSON.parse(response);

            if ( $.fn.DataTable.isDataTable('#presentationTable') ) {
                $('#presentationTable').DataTable().destroy();
            }

            $('#presentationTableBody').html('');
            $.each(response.data, function(i, presentation) {

                // console.log(presentation.id);
              getUndownloadedData(presentation.id);

                let statusBadge = (presentation.uploadStatus)?'<span class="badge badge-success mr-1"><i class="fas fa-check-circle"></i> '+presentation.uploadStatus+' File(s) uploaded</span>':'<span class="badge badge-warning mr-1"><i class="fas fa-exclamation-circle"></i> No Uploads</span>';
                statusBadge += (presentation.active==1)?'<span class="active-status badge badge-success" presentation-id="'+presentation.id+'"><i class="fas fa-check"></i> Active</span>':'<span class="disabled-status badge badge-danger" presentation-id="'+presentation.id+'"><i class="fas fa-times"></i> Disabled</span>';
                statusBadge += '<span  id="undownloadedFileCount_'+presentation.id+'" style="display: none; margin-top:4px"></span>'

                let filesBtn = '<button class="files-btn btn btn-sm btn-info text-white" session-name="'+presentation.session_name+'" presentation-name="'+presentation.name+'" user-id="'+presentation.presenter_id+'" presentation-id="'+presentation.id+'" room_id="'+presentation.room_id+'" room_name="'+presentation.room_name+'" presentation_date="'+presentation.presentation_date+'"><i class="fas fa-folder-open"></i> Files</button>';
                let logsBtn = '<button class="presentation-logs-btn btn btn-sm btn-warning text-white mt-1" session-name="'+presentation.session_name+'" presentation-name="'+presentation.name+'" user-id="<?=$this->session->userdata('user_id')?>" presentation-id="'+presentation.id+'" room_name="'+presentation.room_name+'" presentation_date="'+presentation.presentation_date+'"><i class="fas fa-history"></i> Logs</button>';

                let editBtn = '<button class="edit-presentation-btn btn btn-sm btn-primary text-white" presentation-id="'+presentation.id+'"   user-id="'+presentation.presenter_id+'"  room_id="'+presentation.room_id+'" upload-status="'+presentation.uploadStatus+'"><i class="fas fa-edit"></i> Edit</button>';
                let disableBtn = (presentation.active==0)?'<button class="activate-presentation-btn btn btn-sm btn-success text-white mt-1" presentation-id="'+presentation.id+'"><i class="fas fa-check"></i> Activate</button>':'<button class="disable-presentation-btn btn btn-sm btn-danger text-white mt-1" presentation-id="'+presentation.id+'"><i class="fas fa-times"></i> Disable</button>';

                if(presentation.presentation_date !== null){
                    presentation_date = presentation.presentation_date;
                }else{
                    presentation_date ='';
                }
                if(presentation.start_time !== null && presentation.end_time !== null){
                    presentation_time = convertTime(presentation.start_time)+' - '+convertTime(presentation.end_time);
                }else{
                    presentation_time = '';
                }

                $('#presentationTableBody').append('' +
                    '<tr>\n' +
                    '  <td>\n' +
                    '    '+statusBadge+'\n' +
                    '  </td>\n' +
                    '  <td>'+presentation.id+'</td>\n' +
                    '  <td style="white-space: nowrap">'+presentation_date+'<br>'+presentation_time+'</td>\n' +
                    '  <td style="white-space: nowrap">'+convertTime(presentation.presentation_start)+'</td>\n' +
                    '  <td>'+presentation.room_name+'</td>\n' +
                    '  <td>'+presentation.session_name+'</td>\n' +
                    '  <td>'+presentation.name+'</td>\n' +
                    '  <td>'+presentation.presenter_name+'</td>\n' +
                    '  <td style="width: 200px !important; word-break:break-word">'+presentation.email+'</td>\n' +
                    '  <td>\n' +
                    '    '+filesBtn+'\n' +
                    '    '+logsBtn+'\n' +
                    '  </td>\n' +
                    '  <td>\n' +
                    '   '+editBtn+'\n' +
                    '   '+disableBtn+'\n' +
                    '  </td>\n' +
                    '</tr>');
            });

            $('#presentationTable').DataTable({
                initComplete: function() {
                    $(this.api().table().container()).find('input').attr('autocomplete', 'off');
                    $(this.api().table().container()).find('input').attr('type', 'text');
                    $(this.api().table().container()).find('input').val('upload');
                    //$(this.api().table().container()).find('input').val('');
                },

            });

            $('#lastUpdated').text(formatDateTime(response.data[0].created_on, false));
            $('#lastUpdatedAlert').show();
        })
            .fail(function(response) {
                $('#sessionsTable').DataTable();
                toastr.error("Unable to load your presentations data");
            });
    }

    function getUndownloadedData(presentation){
        $.get( "<?=base_url('admin/dashboard/getdata/')?>"+presentation, function(response) {
            if(response > 0){
               $('#undownloadedFileCount_'+presentation).html('<i class="fas fa-bell" style="color: red"></i> Undownloaded File(s)'+response);
               $('#undownloadedFileCount_'+presentation).css('display', 'block');
               $('#undownloadedFileCount_'+presentation).attr('class', 'badge badge-warning');
            }
        })
    }
    
    function formatDateTime(datetimeStr, include_year = true) {
        let lastUpdatedDate = new Date(datetimeStr);
        let year = new Intl.DateTimeFormat('en', { year: 'numeric' }).format(lastUpdatedDate);
        let month = new Intl.DateTimeFormat('en', { month: 'long' }).format(lastUpdatedDate);
        let day = new Intl.DateTimeFormat('en', { day: '2-digit' }).format(lastUpdatedDate);
        let time = lastUpdatedDate.toLocaleTimeString('en-US', { hour: 'numeric', hour12: true, minute: 'numeric' });

        return ((include_year)?year+' ':'')+month+', '+day+'th '+time;
    }

    function activatePresentation(presentation_id, button) {
        $.get( "<?=base_url('admin/dashboard/activatePresentation/')?>"+presentation_id, function(response) {
            response = JSON.parse(response);

            if (response.status == 'success')
            {
                $('.disabled-status[presentation-id="'+presentation_id+'"]').html('<i class="fas fa-check"></i> Active');
                $('.disabled-status[presentation-id="'+presentation_id+'"]').removeClass('badge-danger');
                $('.disabled-status[presentation-id="'+presentation_id+'"]').addClass('badge-success');
                $('.disabled-status[presentation-id="'+presentation_id+'"]').addClass('active-status');
                $('.disabled-status[presentation-id="'+presentation_id+'"]').removeClass('disabled-status');

                button.removeClass('activate-presentation-btn');
                button.addClass('disable-presentation-btn');
                button.removeClass('btn-success');
                button.addClass('btn-danger');
                button.html('<i class="fas fa-times"></i> Disable');

                toastr.success(response.msg);
            }else{
                toastr.error(response.msg);
            }

        }).fail(function() {
            toastr.error('Unable activate the presentation');
        })
    }

    function disablePresentation(presentation_id, button) {
        $.get( "<?=base_url('admin/dashboard/disablePresentation/')?>"+presentation_id, function(response) {
            response = JSON.parse(response);

            if (response.status == 'success')
            {
                $('.active-status[presentation-id="'+presentation_id+'"]').html('<i class="fas fa-times"></i> Disabled');
                $('.active-status[presentation-id="'+presentation_id+'"]').removeClass('badge-success');
                $('.active-status[presentation-id="'+presentation_id+'"]').addClass('badge-danger');
                $('.active-status[presentation-id="'+presentation_id+'"]').addClass('disabled-status');
                $('.active-status[presentation-id="'+presentation_id+'"]').removeClass('active-status');

                button.removeClass('disable-presentation-btn');
                button.addClass('activate-presentation-btn');
                button.removeClass('btn-danger');
                button.addClass('btn-success');
                button.html('<i class="fas fa-check"></i> Activate');

                toastr.success(response.msg);
            }else{
                toastr.error(response.msg);
            }

        }).fail(function() {
            toastr.error('Unable disable the presentation');
        })
    }

</script>
<script>
    function convertTime(timeString){
        var H = +timeString.substr(0, 2);
        var h = (H % 12) || 12;
        var ampm = H < 12 ? " AM" : " PM";
        var single = H < 10 ? "0" : '';
        timeString = single + h + timeString.substr(2, 3) + ampm;
        return timeString;
    }

</script>

