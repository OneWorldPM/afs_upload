<?php ?>
<!-- Modal -->
<div class="modal fade" id="createPresentationModal" tabindex="-1" role="dialog" aria-labelledby="createPresentationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createPresentationModalTitle">Create Presentation</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">

                <form id="formPresentation" action="<?=base_url()?>admin/dashboard/save_presentation" method="post">
                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <span class="input-group-text">Session Name</span>
                    </div>
                    <input name="session_name" id="session_name" type="text" class="form-control" aria-label="Session Name" required>
                </div>
                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <span class="input-group-text">Session Full Name</span>
                    </div>
                    <input name="session_full_name" id="session_full_name" type="text" class="form-control" aria-label="Session Name" required>
                </div>
                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <span class="input-group-text">Presentation Title</span>
                    </div>
                    <input name="presentation_title" id="presentation_title" type="text" class="form-control" aria-label="Session Name" required>
                </div>
                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <span class="input-group-text">Presenter</span>
                    </div>
                    <select name="presenter_id" class="form-control" id="presenters_list"  >
                        <option> Select Presenter </option>
                    </select>
                </div>

                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <span class="input-group-text">Room Name</span>
                    </div>
                        <input name="room_name" id="room_name" type="text" class="form-control" aria-label="Room Name" required>
                </div>

                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <span class="input-group-text">Presentation Date</span>
                    </div>
                    <input name="presentation_date" id="presentation_date" type="date" class="form-control" aria-label="Presentation Date" required>
                </div>

                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <span class="input-group-text">Session Start Time</span>
                    </div>
                    <input name="session_start" id="session_start" type="time" class="form-control" aria-label="Session Start Time" required>
                </div>

                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <span class="input-group-text">Session End Time</span>
                    </div>
                    <input name="session_end" id="session_end" type="time" class="form-control" aria-label="Session End Time" required>
                </div>

                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <span class="input-group-text">Presentation Start Time</span>
                    </div>
                    <input name="presentation_start" id="presentation_start" type="time" class="form-control" aria-label="Session Start Time" required>
                </div>
                    <button type="submit" class="btn btn-primary" id="savePresentationBtn">Save changes</button>
                </form>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>

            </div>
        </div>
    </div>
</div>

<script>
    $(function(){
        let base_url = "<?=base_url()?>";
        $('.create-presentation-btn').on('click', function(){
            $('#presenters_list').html('');
            $.post(base_url+'/admin/dashboard/get_presenter',
                function(presenters){
                console.log(presenters);
                    $.each(presenters, function(index, presenter){
                        $('#presenters_list').append('<option value="'+presenter.presenter_id+'">'+presenter.first_name+''+presenter.last_name+'</option>');
                    })
                },'json')
        })




    })
</script>