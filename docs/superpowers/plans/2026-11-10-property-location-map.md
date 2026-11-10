# Property image and location steps

- Move the existing image drawing card, unchanged, to step one beside the basic fields in the existing bordered card style. Keep the descriptive boundaries table in step two.
- Add a Google Maps picker to step two, using the configured API key. Persist the selected latitude, longitude and canonical Maps URL through the existing form and draft payload.
- Support place names, coordinates, long Maps links and short Maps links; expand short links through an authenticated, permission checked endpoint with restricted Google hosts and redirects.
- Initialize the shared property script on create and edit. Restore the selected marker after draft restoration, including when the Maps API finishes loading later. Keep existing image restoration intact.
- Retain original uploaded images as tenant attachments for subsequent boundary edits. Keep legacy annotated images unchanged until the original is supplied, ignore stale image callbacks and flush pending drawing changes before draft serialization.
- Permit Maps tile and service resources through CSP on the property form routes only, retaining nonce based script loading.
- Verify parsing and picker behavior with Node tests, URL expansion with HTTP fakes, rendered step ownership and draft round trips with focused PHP tests, then compile Blade and build assets.

Review focus: coordinate zero and range handling; exact place coordinates versus map camera center; short-link redirects to untrusted hosts; restored coordinates taking precedence over an older URL; async initialization and restored image rendering.
