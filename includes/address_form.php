<?php
// Expects $prefix (e.g. 'addr') and $data (array) to be set by caller
$p = $prefix ?? 'addr';
$d = $data ?? [];

?>
<script>
    console.log('Address Form Debug -> Prefix ($p):', <?= json_encode($p) ?>);
    console.log('Address Form Debug -> Data ($d):', <?= json_encode($d) ?>);
</script>
<?php
// Helper to get value safely
function _addr_val($d, $key) {
    return htmlspecialchars($d[$key] ?? '');
}

// Construct a display string for the search box if we have data
$displayString = '';
if (!empty($d[$p.'_street'])) {
    $parts = [];
    $unitAndNumber = [];
    if (!empty($d[$p.'_unit'])) $unitAndNumber[] = $d[$p.'_unit'];
    if (!empty($d[$p.'_number'])) $unitAndNumber[] = $d[$p.'_number'];
    if (!empty($unitAndNumber)) {
        $parts[] = implode('/', $unitAndNumber);
    }
    $parts[] = $d[$p.'_street'] . ',';
    if (!empty($d[$p.'_suburb'])) $parts[] = $d[$p.'_suburb'];
    if (!empty($d[$p.'_state'])) $parts[] = $d[$p.'_state'] . ',';
    if (!empty($d[$p.'_postcode'])) $parts[] = $d[$p.'_postcode'];
    $displayString = implode(' ', $parts);
}

// Construct a query string for Google Maps
$mapQuery = '';
if (!empty($d[$p.'_street'])) {
    $mapParts = [];
    // Don't use unit for map query, it's often not useful
    if (!empty($d[$p.'_number'])) $mapParts[] = $d[$p.'_number'];
    if (!empty($d[$p.'_street'])) $mapParts[] = $d[$p.'_street'];
    if (!empty($d[$p.'_suburb'])) $mapParts[] = $d[$p.'_suburb'];
    if (!empty($d[$p.'_state'])) $mapParts[] = $d[$p.'_state'];
    if (!empty($d[$p.'_postcode'])) $mapParts[] = $d[$p.'_postcode'];
    $mapQuery = urlencode(implode(' ', $mapParts));
}

// Fetch API Key from database config (requires auth.php to be loaded by parent)
$googleMapsKey = function_exists('get_config') ? get_config('google_maps_key') : '';
?>
<div class="address-component" data-prefix="<?= $p ?>">
    <label>Address Lookup</label>
    <div class="address-wrapper">
        <input type="text" class="address-search" placeholder="Start typing address..." value="<?= htmlspecialchars($displayString) ?>" autocomplete="off">
        <?php if ($mapQuery): ?>
            <a href="https://maps.google.com/?q=<?= $mapQuery ?>" target="_blank" class="address-map-link" title="View on Map">🗺️</a>
        <?php endif; ?>
        <div class="address-results"></div>
    </div>
    
    <div class="address-manual-toggle" onclick="toggleManual('<?= $p ?>')">Edit Address Details Manually</div>

    <div class="manual-fields" id="manual_<?= $p ?>">
        <div class="flex gap-10">
            <div style="flex: 1;">
                <label>Unit</label>
                <input type="text" name="<?= $p ?>_unit" class="addr-unit" value="<?= _addr_val($d, $p.'_unit') ?>">
            </div>
            <div style="flex: 1;">
                <label>Number</label>
                <input type="text" name="<?= $p ?>_number" class="addr-number" value="<?= _addr_val($d, $p.'_number') ?>">
            </div>
        </div>

        <label>Street</label>
        <input type="text" name="<?= $p ?>_street" class="addr-street" value="<?= _addr_val($d, $p.'_street') ?>">

        <div class="flex gap-10">
            <div class="flex-1">
                <label>Suburb</label>
                <input type="text" name="<?= $p ?>_suburb" class="addr-suburb" value="<?= _addr_val($d, $p.'_suburb') ?>">
            </div>
            <div style="width: 80px;">
                <label>State</label>
                <input type="text" name="<?= $p ?>_state" class="addr-state" value="<?= _addr_val($d, $p.'_state') ?>">
            </div>
            <div style="width: 100px;">
                <label>Postcode</label>
                <input type="text" name="<?= $p ?>_postcode" class="addr-postcode" value="<?= _addr_val($d, $p.'_postcode') ?>">
            </div>
        </div>
    </div>
</div>
<script>
function toggleManual(prefix) {
    document.getElementById('manual_' + prefix).classList.toggle('open');
}
</script>
<?php if (!defined('GOOGLE_MAPS_SCRIPTS_LOADED')): define('GOOGLE_MAPS_SCRIPTS_LOADED', true); ?>
    <!-- Added timestamp to force reload of cached JS -->
    <script src="/js/address.js?v=<?= time() ?>"></script>
    <?php if ($googleMapsKey): ?>
        <script src="https://maps.googleapis.com/maps/api/js?key=<?= htmlspecialchars($googleMapsKey) ?>&v=beta&callback=initGoogleMaps&loading=async" async defer></script>
    <?php else: ?>
        <script>console.error("Google Maps API Key is missing from app_config table.");</script>
    <?php endif; ?>
<?php endif; ?>