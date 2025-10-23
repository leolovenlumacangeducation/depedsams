<!-- Change SEP Photo Modal -->
<div class="modal fade" id="changeSepPhotoModal" tabindex="-1" aria-labelledby="changeSepPhotoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="changeSepPhotoForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="changeSepPhotoModalLabel">Change SEP Photo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="photo_sep_id" name="sep_id">
                    <div class="mb-3 text-center">
                        <img id="sep-photo-preview" src="" class="img-fluid rounded bg-light mb-3" alt="Current Photo" style="max-height: 250px; object-fit: contain;">
                    </div>
                    <div class="mb-3">
                        <label for="sep_photo_file" class="form-label">Select new photo (PNG, JPG, WEBP)</label>
                        <input class="form-control" type="file" id="sep_photo_file" name="sep_photo" accept="image/png, image/jpeg, image/webp" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-upload"></i> Upload & Save</button>
                </div>
            </form>
        </div>
    </div>
</div>