<!-- Change Consumable Photo Modal -->
<div class="modal fade" id="changePhotoModal" tabindex="-1" aria-labelledby="changePhotoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="changePhotoForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="changePhotoModalLabel">Change Consumable Photo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="photo_consumable_id" name="consumable_id">
                    <div class="mb-3 text-center">
                        <img id="photo-preview" src="" class="img-fluid rounded bg-light mb-3" alt="Current Photo" style="max-height: 250px; object-fit: contain;">
                    </div>
                    <div class="mb-3">
                        <label for="consumable_photo_file" class="form-label">Select new photo (PNG, JPG, GIF, WEBP)</label>
                        <input class="form-control" type="file" id="consumable_photo_file" name="consumable_photo" accept="image/png, image/jpeg, image/gif, image/webp" required>
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