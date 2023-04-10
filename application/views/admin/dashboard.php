<title>Admin - AFS  Presentations</title>

<main role="main" style="margin-top: 70px;margin-left: 20px;margin-right: 20px;">
    <div class="row">
        <div class="col-md-12">
            <h3>Presentations</h3>
            <p>Loaded presentations are listed here</p>
            <h6 class="text-info">Tip:  Click on multiple records to group select</h6><br>

            <div id="lastUpdatedAlert" class="alert alert-warning alert-dismissible fade show" role="alert" style="display:none;">
                This list was last loaded on <strong><span id="lastUpdated"></span></strong>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

        </div>
        <a href="<?=base_url().'admin/dashboard/presentationToCsv'?>" target="_blank" class="btn btn-primary float-left mb-2 text-white" style="cursor: pointer"><i class="fas fa-file-csv"></i> Export CSV</a>
        <a href="#" target="_blank" class="btn btn-primary float-left mb-2 ml-5 text-white" style="cursor: pointer" id="downloadSelectedPresentation"><i class="fas fa-file-archive"></i> Zip & Download  Selected Presentation</a>
        <div class="col-md-12">
            <button class="create-presentation-btn btn btn-success float-right"><i class="fas fa-plus"></i> Create New</button>
            <button class="select-all-presentation btn btn-info btn-sm mr-2 float-left"><i class="fas fa-check-double"></i> Select All Filtered</button>
            <table id="presentationTable" class="table table-striped table-bordered" style="width:100%">
                <thead>
                <tr>
                    <!--                    <th>Select All</th>-->
                    <!--                    <th><input type="checkbox" name="check" id="checkAllPresentation">Select All</th>-->
                    <th>Status<br> <select class="filter-status" style="width:100px" >
                            <option value=""></option>
                            <?php if(isset($new_uploads) && !empty($new_uploads)):?>
                                <option value="new-uploads" presentation-ids="<?=$new_uploads?>">New Uploads</option>
                            <?php else: ?>
                                <option value="new-uploads" presentation-ids="">New Uploads</option>
                            <?php endif; ?>
                            <option value="active">Active</option>
                            <option value="disabled">Disabled</option>
                        </select></th>
                    <th>ID</th>
                    <th>Assigned ID</th>
                    <th style=" white-space: nowrap ">Session Date</th>
                    <th>Presentation Start</th>
                    <th>Room</th>
                    <th>Session Name</th>
                    <th>Presentation Title</th>
                    <th>Presenter FirstName</th>
                    <th>Presenter LastName</th>
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

<div class="modal fade" id="logsModal" tabindex="-1" role="dialog" aria-labelledby="logsModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logsModalLabel">Logs (<span id="logPersonName"></span>)</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="height: 500px;overflow: scroll;">
                <ul id="logsList" class="list-group">
                    <!-- Will be filled by JS -->
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.datatables.net/1.10.23/js/jquery.dataTables.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/1.10.23/js/dataTables.bootstrap4.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/select/1.3.4/js/dataTables.select.min.js" crossorigin="anonymous"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.23/css/dataTables.bootstrap4.min.css" crossorigin="anonymous" />
<link rel="stylesheet" href="https://cdn.datatables.net/select/1.3.4/css/select.dataTables.min.css" crossorigin="anonymous" />


<script>
    let selected = [];
    let allFiltered = [];
    let isAllSelected = false;
    let presentationDt;
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
            toastr['info']('Please wait...');

            let userId = $(this).attr('user-id');
            $.get(`<?=base_url()?>admin/dashboard/getLogs/${userId}`, function (logs) {
                logs=JSON.parse(logs);

                if (logs.length == 0)
                {
                    toastr['warning']('No logs found');
                    return false;
                }

                $('#logPersonName').text(logs[0].first_name+' '+logs[0].last_name);

                $('#logsList').html('');
                $.each(logs, function(i, log){

                    let presentation_name = (log.ref_presentation_id != null)?`<br><span><small>Presentation: ${log.name}</small></span>`:'';
                    let file = (log.other_ref != null)?`<br><span><small>File: <a href="<?=base_url()?>admin/dashboard/openFile/${log.other_ref}">${log.file_name}</a></small></span>`:'';

                    $('#logsList').append('' +
                        `<li class="list-group-item">
                                <i style="color: ${log.color}" class="${log.icon}"></i>
                                ${log.log_name}
                                <small class="float-right">${log.date_time}</small>
                                ${presentation_name}
                                ${file}
                             </li>`);
                });

                $('body').find('#toast-container').remove();
                $('#logsModal').modal('show');
            });
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


        $('.select-all-presentation').on('click', function(){
            if (isAllSelected == false && selected.length == 0)
            {
                selected = allFiltered;
                presentationDt.rows().every(function() {
                    this.nodes().to$().addClass('selected');
                });

                $(this).removeClass('btn-info');
                $(this).addClass('btn-danger');
                $(this).html('<i class="fas fa-ban"></i> Unselect All');
                isAllSelected = true;
            }else{
                selected = [];
                presentationDt.rows().every(function() {
                    this.nodes().to$().removeClass('selected');
                });

                $(this).removeClass('btn-danger');
                $(this).addClass('btn-info');
                $(this).html('<i class="fas fa-check-double"></i> Select All Filtered');
                isAllSelected = false;
            }
        });

        $('#downloadSelectedPresentation').on('click', function(e){
            e.preventDefault();
            toastr['info']('please wait...')

            checkedPresentationIds = selected.join('-')


            $.post('<?=base_url()?>admin/dashboard/download_checked_presentation_zip/',
                {
                    'checkedPresentationIds':checkedPresentationIds
                },
                function(response){
                    response = JSON.parse(response);

                 if(response.status === 'success'){
                     toastr.clear();
                     Swal.fire({
                         title: 'Done!',
                         html:`<a href="<?=base_url()?>${response.file_name}" download><h3> Click here to Download Files <i class="fas fa-download"></i></h3></a>
                         <br><small class="text-danger">Downloading zip files will not affect undownloaded file status</small>`,
                         icon: 'success',
                         confirmButtonText: 'Close'
                 })
                 }else{
                     toastr.clear();
                     toastr['error'](response.msg);
                 }

            })
        })
        $('.clear-filter').on('click', function(e){
            e.preventDefault()
            let index = $(this).attr('input-index');
            $('#presentationTable .filter_'+index).val('');
            $('#presentationTable .filter-select_'+index).val('');
            $('.filter_'+index).change();
        })

        $('.filter-select').val('');
        $('.filter-status').val('');

        $('.filter-select').on('change', function(){
            let index = $(this).parent().attr('input-index');
            $('#presentationTable .filter_'+index).val($(this).val());
            $('.filter_'+index).change();
        })

        $('.filter-status').on('change', function(){

            let presentationsWithNewUploads = $('option:selected', this).attr('presentation-ids');
            console.log(  presentationDt.column($(this).data('column')));
            if($(this).val() == 'new-uploads') {
                presentationDt.column($(this).data('column'))
                    .search(presentationsWithNewUploads)
                    .draw();
            }
            else {
                presentationDt.column($(this).data('column'))
                    .search($(this).val())
                    .draw();
            }
        })

    } );

    //function loadPresentations2() {
    //
    //    $('#searchAllInput').on( 'keyup', function () {
    //        presentationDt.search( this.value ).draw();
    //    } );
    //
    //    $('#presentationTable thead th').each(function(i) {
    //        let $this = $(this).text();
    //
    //        if (!($this == 'Actions' || $this== 'Status' || $this== 'Select All' || $this== 'Info' || $this== 'ID'))
    //            $(this).html($(this).text()+'<br><input class="filter_'+i+'" type="text" placeholder="Search '+$(this).text()+'" style="width: inherit;background: white;color: black;border: 1px solid #666;"/><br><input type="button" value="Clear" class="clear-filter btn btn-sm btn-warning" style="width: 80%; height: 20px; padding-top: 0" id="clear-filter" input-index="'+i+'"/>');
    //        // <br><input type="button" value="Clear" class="clear-filter btn btn-sm btn-warning" style="width: 80%; height: 20px; padding-top: 0"/>
    //        // <button class="clear-filter badge badge-warning badge-sm mr-2 float-left " style="width: 100%;" id="clear-filter" input-index="'+i+'"><i class="fas fa-eraser"></i> Clear</button>
    //    });
    //    $('#presentationTable tfoot td').each(function(i) {
    //        let $this = $(this).text();
    //
    //        if (!($this == 'Actions' || $this== 'Status' || $this== 'Select All' || $this== 'Info' || $this== 'ID'))
    //            $(this).attr('input-index', i);
    //            $(this).find('select').addClass('filter-select_'+i)
    //    });
    //
    //    if ( $.fn.DataTable.isDataTable('#presentationTable') ) {
    //        $('#presentationTable').DataTable().destroy();
    //
    //        selectPresentationRow();
    //    }
    //
    //    let presentation_ids = [];
    //    presentationDt = $('#presentationTable')
    //        .DataTable(
    //            {
    //
    //                "serverSide": true,
    //                "processing": true,
    //                lengthMenu: [5, 10, 20, 50, 100, 200],
    //                "iDisplayLength": 200,
    //                "ajax":
    //                    {
    //                        "url": "<?//=base_url()?>//admin/dashboard/getPresentationsDt",
    //                        "type": "POST"
    //                    },
    //
    //                "columns":
    //                    [
    //
    //                        { "name": "new-uploads", "data": null, render: function(presentation, type, row, meta) {
    //                                getUndownloadedData(presentation.id, presentation.uploadStatus);
    //
    //                                let statusBadge = (presentation.uploadStatus)?'<span class="badge badge-success mr-1"><i class="fas fa-check-circle"></i> '+presentation.uploadStatus+' File(s) uploaded</span>':'<span class="badge badge-warning mr-1"><i class="fas fa-exclamation-circle"></i> No Uploads</span>';
    //                                statusBadge += (presentation.active==1)?'<span class="active-status badge badge-success" presentation-id="'+presentation.id+'"><i class="fas fa-check"></i> Active</span>':'<span class="disabled-status badge badge-danger" presentation-id="'+presentation.id+'"><i class="fas fa-times"></i> Disabled</span>';
    //                                statusBadge += '<span  id="undownloadedFileCount_'+presentation.id+'" style="display: none; margin-top:4px; "></span>';
    //
    //                                return  statusBadge;
    //                            }
    //                        },
    //
    //                        { "name": "p.id", "data": "id"},
    //                        { "name": "p.assigned_id", "data": "assigned_id"},
    //                        { "name": "p.presentation_date", "data": "presentation_date", "width": "105px" },
    //                        { "name": "p.presentation_start", "data": "presentation_start" },
    //                        { "name": "rm.name", "data": "room_name" },
    //                        { "name": "s.name", "data": "session_name" },
    //                        { "name": "p.name", "data": "name" },
    //                        { "name": "pr.first_name", "data": "first_name" },
    //                        { "name": "pr.last_name", "data": "last_name" },
    //                        { "name": "pr.email", "data": "email" },
    //                        { "action": "action", "data": null, render: function(presentation, type, row, meta) {
    //                                let filesBtn = '<button class="files-btn btn btn-sm btn-info text-white" session-name="'+presentation.session_name+'" presentation-name="'+presentation.name+'" user-id="'+presentation.presenter_id+'" presentation-id="'+presentation.id+'" room_id="'+presentation.room_id+'" room_name="'+presentation.room_name+'" presentation_date="'+presentation.presentation_date+'"><i class="fas fa-folder-open"></i> Files</button>';
    //                                let logsBtn = '<button class="presentation-logs-btn btn btn-sm btn-warning text-white mt-1" session-name="'+presentation.session_name+'" presentation-name="'+presentation.name+'" user-id="'+presentation.presenter_id+'" presentation-id="'+presentation.id+'" room_name="'+presentation.room_name+'" presentation_date="'+presentation.presentation_date+'"><i class="fas fa-history"></i> Logs</button>';
    //
    //                                return filesBtn+' '+logsBtn;
    //                            }
    //                        },
    //                        { "action": "action", "data": null, render: function(presentation, type, row, meta) {
    //                                let editBtn = '<button class="edit-presentation-btn btn btn-sm btn-primary text-white" presentation-id="'+presentation.id+'"   user-id="'+presentation.presenter_id+'"  room_id="'+presentation.room_id+'" upload-status="'+presentation.uploadStatus+'"><i class="fas fa-edit"></i> Edit</button>';
    //                                let disableBtn = (presentation.active==0)?'<button class="activate-presentation-btn btn btn-sm btn-success text-white mt-1" presentation-id="'+presentation.id+'"><i class="fas fa-check"></i> Activate</button>':'<button class="disable-presentation-btn btn btn-sm btn-danger text-white mt-1" presentation-id="'+presentation.id+'"><i class="fas fa-times"></i> Disable</button>';
    //
    //                                return editBtn+' '+disableBtn;
    //                            }
    //                        },
    //
    //                    ],
    //                "paging": true,
    //                "lengthChange": true,
    //                "searching": true,
    //                "ordering": true,
    //                "info": true,
    //                "autoWidth": false,
    //                "responsive": false,
    //                "createdRow": function(row, data, dataIndex){
    //                    $(row).attr('id', data.id);
    //                },
    //                "rowCallback": function( row, data ) {
    //                    if (selected.includes(parseInt(data.id))) {
    //                        $(row).addClass('selected');
    //                    }
    //                },
    //                "order": [[ 3, "ASC" ]],
    //
    //
    //                initComplete: function(settings, json) {
    //                    var api = this.api();
    //                    // Apply the search
    //                    arr = ['2','3','4','5','6','7','8','9','10'];
    //                    api.columns(arr).every(function() {
    //                        var that = this;
    //                        $('input', this.header()).on('keyup change', function() {
    //                            if (that.search() !== this.value) {
    //                                that.search(this.value).draw();
    //                            }
    //                        });
    //                    });
    //                    $('[data-toggle="tooltip"]').tooltip();
    //                },
    //                "drawCallback": function(settings) {
    //                    allFiltered = this.api().ajax.json().total_filtered;
    //                }
    //
    //            });
    //    selectPresentationRow();
    //
    //}


    function selectPresentationRow(){
        $('#presentationTable tbody').on('click', 'tr', function () {
            var id = parseInt(this.id);
            var index = $.inArray(id, selected);

            if ( index === -1 ) {
                selected.push( id );
            } else {
                selected.splice( index, 1 );
            }

            $(this).toggleClass('selected');
            console.log(selected.length);
            if(selected.length > 0)
            {
                $('.select-all-presentation').removeClass('btn-info');
                $('.select-all-presentation').addClass('btn-danger');
                $('.select-all-presentation').html('<i class="fas fa-ban"></i> Unselect All');
            }else{
                $('.select-all-presentation').removeClass('btn-danger');
                $('.select-all-presentation').addClass('btn-info');
                $('.select-all-presentation').html('<i class="fas fa-check-double"></i> Select All');
            }
        } );
    }




    function loadPresentations() {
        $.get( "<?=base_url('admin/dashboard/getPresentationList')?>", function(response) {
            response = JSON.parse(response);

            if ( $.fn.DataTable.isDataTable('#presentationTable') ) {
                $('#presentationTable').DataTable().destroy();
                selectPresentationRow();
            }

            $('#presentationTableBody').html('');
            $.each(response.data, function(i, presentation) {

                // console.log(presentation.id);
              getUndownloadedData(presentation.id, presentation.uploadStatus);

                let statusBadge = (presentation.uploadStatus)?'<span class="badge badge-success mr-1"><i class="fas fa-check-circle"></i> '+presentation.uploadStatus+' File(s) uploaded</span>':'<span class="badge badge-warning mr-1"><i class="fas fa-exclamation-circle"></i> No Uploads</span>';
                statusBadge += (presentation.active==1)?'<span class="active-status badge badge-success" presentation-id="'+presentation.id+'"><i class="fas fa-check"></i> Active</span>':'<span class="disabled-status badge badge-danger" presentation-id="'+presentation.id+'"><i class="fas fa-times"></i> Disabled</span>';
                statusBadge += '<span  id="undownloadedFileCount_'+presentation.id+'" style="display: none; margin-top:4px; "></span>'

                let filesBtn = '<button class="files-btn btn btn-sm btn-info text-white" session-name="'+presentation.session_name+'" presentation-name="'+presentation.name+'" user-id="'+presentation.presenter_id+'" presentation-id="'+presentation.id+'" room_id="'+presentation.room_id+'" room_name="'+presentation.room_name+'" presentation_date="'+presentation.presentation_date+'"><i class="fas fa-folder-open"></i> Files</button>';
                let logsBtn = '<button class="presentation-logs-btn btn btn-sm btn-warning text-white mt-1" session-name="'+presentation.session_name+'" presentation-name="'+presentation.name+'" user-id="'+presentation.presenter_id+'" presentation-id="'+presentation.id+'" room_name="'+presentation.room_name+'" presentation_date="'+presentation.presentation_date+'"><i class="fas fa-history"></i> Logs</button>';

                let editBtn = '<button class="edit-presentation-btn btn btn-sm btn-primary text-white" presentation-id="'+presentation.id+'"   user-id="'+presentation.presenter_id+'"  room_id="'+presentation.room_id+'" upload-status="'+presentation.uploadStatus+'"><i class="fas fa-edit"></i> Edit</button>';
                let disableBtn = (presentation.active==0)?'<button class="activate-presentation-btn btn btn-sm btn-success text-white mt-1" presentation-id="'+presentation.id+'"><i class="fas fa-check"></i> Activate</button>':'<button class="disable-presentation-btn btn btn-sm btn-danger text-white mt-1" presentation-id="'+presentation.id+'"><i class="fas fa-times"></i> Disable</button>';

                let presentationCheckbox = '<input type="checkbox" class="checkedPresentation" name="checkedPresentation" id="checkedPresentation_'+presentation.id+'" presentation-id="'+presentation.id+'" room-id="'+presentation.room_id+'" presenter-id="'+presentation.presenter_id+'" session-id="'+presentation.session_id+'">';
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
                    '<tr id="'+presentation.id+'">\n' +
                    '  <td>\n' +
                    '    '+statusBadge+'\n' +
                    '  </td>\n' +
                    '  <td>'+presentation.id+'</td>\n' +
                    '  <td>'+presentation.assigned_id+'</td>\n' +
                    '  <td style="white-space: nowrap">'+presentation_date+'<br>'+presentation_time+'</td>\n' +
                    '  <td style="white-space: nowrap">'+convertTime(presentation.presentation_start)+'</td>\n' +
                    '  <td>'+presentation.room_name+'</td>\n' +
                    '  <td>'+presentation.session_name+'</td>\n' +
                    '  <td>'+presentation.name+'</td>\n' +
                    '  <td>'+presentation.first_name+'</td>\n' +
                    '  <td>'+presentation.last_name+'</td>\n' +
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

            presentationDt = $('#presentationTable')
                .DataTable({
                    lengthMenu: [[5, 25, 50, 250, -1], [5, 25, 50, 250, "All"]],
                    "iDisplayLength": -1,
                initComplete: function() {
                   // Get the search box input element
                    var searchInput = $('div.dataTables_filter input');

                    // Generate a random name for the search input
                    var randomName = "search_" + Math.random().toString(36).substring(2);

                    // Set the name and autocomplete attributes of the search input
                    searchInput.attr('name', randomName).attr('type', 'password');
                    
                    searchInput.on('click', function(){
                        searchInput.attr('name', randomName).attr('type', 'text');
                    })
                    // $('#presentationTable_filter').find('input').val('upload');
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

        selectPresentationRow();
    }

    function getUndownloadedData(presentation, upload_status){
        $.get( "<?=base_url('admin/dashboard/getUploadsCount/')?>"+presentation, function(response) {
            // console.log(response);
            if(response.upload_count == response.undownloaded){
               $('#undownloadedFileCount_'+presentation).html('<i class="fas fa-bell" style="color: red"></i>'+ response.upload_count+' New File(s)');
               $('#undownloadedFileCount_'+presentation).css('display', 'block');
               $('#undownloadedFileCount_'+presentation).attr('class', 'badge badge-warning');
            }else if(response.undownloaded >0){
                $('#undownloadedFileCount_'+presentation).html('<i class="fas fa-bell" style="color: red"></i> '+ response.upload_count+' New File(s)');
                $('#undownloadedFileCount_'+presentation).css('display', 'block');
                $('#undownloadedFileCount_'+presentation).attr('class', 'badge badge-warning');
            }else{
                $('#undownloadedFileCount_'+presentation).html('<i class="fas fa-check" style="color: white"></i>'+ response.upload_count+' New File(s)');
                $('#undownloadedFileCount_'+presentation).css('display', 'block');
                $('#undownloadedFileCount_'+presentation).attr('class', 'badge badge-success');
            }
        }, 'json')
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

