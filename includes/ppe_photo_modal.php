<!-- Change PPE Photo Modal -->
<div class="modal fade" id="changePpePhotoModal" tabindex="-1" aria-labelledby="changePpePhotoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="changePpePhotoForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="changePpePhotoModalLabel">Change PPE Photo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="photo_ppe_id" name="ppe_id">
                    <div class="mb-3 text-center">
                        <img id="ppe-photo-preview" src="" class="img-fluid rounded bg-light mb-3" alt="Current Photo" style="max-height: 250px; object-fit: contain;">
                    </div>
                    <div class="mb-3">
                        <label for="ppe_photo_file" class="form-label">Select new photo (PNG, JPG, WEBP)</label>
                        <input class="form-control" type="file" id="ppe_photo_file" name="ppe_photo" accept="image/png, image/jpeg, image/webp" required>
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