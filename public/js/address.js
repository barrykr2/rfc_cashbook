window.initGoogleMaps = function() {
    console.log('Address Debug: Init called');
    setupAddressComponents();
};

async function setupAddressComponents() {
    try {
        console.log('Address Debug: Importing libraries...');
        // Ensure we are using the modern library imports
        const { PlaceAutocompleteElement } = await google.maps.importLibrary("places");
        const { LatLng, LatLngBounds } = await google.maps.importLibrary("core");
        console.log('Address Debug: Libraries loaded');

        const components = document.querySelectorAll('.address-component');

        components.forEach(comp => {
            const prefix = comp.dataset.prefix;
            const originalInput = comp.querySelector('.address-search');
            
            // Map fields to an object for cleaner access

            const fields = {
                unit: comp.querySelector(`[name="${prefix}_unit"]`),
                number: comp.querySelector(`[name="${prefix}_number"]`),
                street: comp.querySelector(`[name="${prefix}_street"]`),
                suburb: comp.querySelector(`[name="${prefix}_suburb"]`),
                state: comp.querySelector(`[name="${prefix}_state"]`),
                postcode: comp.querySelector(`[name="${prefix}_postcode"]`)
            };

            // 1. Initialize the modern Web Component instance
            const autocomplete = new PlaceAutocompleteElement({
                includedRegionCodes: ["au"], // The new way to restrict to Australia  //componentRestrictions: { country: ["au"] },
                locationRestriction: new LatLngBounds(
                    new LatLng(-29.18, 138.00),
                    new LatLng(-9.23, 153.55)
                )
            });

            autocomplete.style.width = '100%';

            // Transfer placeholder from original input to the new component
            if (originalInput.placeholder) {
                autocomplete.placeholder = originalInput.placeholder;
            }
            
            // 2. ATTACH LISTENER BEFORE INJECTING INTO DOM
            // This ensures the component is "listening" the moment it connects
            // Use "gmp-select" for the most stable modern performance
            autocomplete.addEventListener("gmp-select", async (event) => {
                console.log("Address Debug: Event fired!", event);
                
                // In the newest API, you get the 'place' from a prediction
                const place = event.placePrediction.toPlace(); 

                if (!place) {
                    console.warn("Address Debug: No place data found in event");
                    return;
                }

                try {
                    // 3. Fetch specific fields required for the breakdown
                    // Note: 'addressComponents' is essential for your logic
                    await place.fetchFields({ 
                        fields: ["addressComponents", "location", "formattedAddress"] 
                    });

                    console.log("Address Debug: Place details fetched", place.addressComponents);

                    // Clear existing field values
                    Object.values(fields).forEach(f => { if(f) f.value = ''; });

                    // 4. Map Google components to your specific inputs
                    if (place.addressComponents) {
                        for (const component of place.addressComponents) {
                            const val = component.longText;
                            const shortVal = component.shortText;
                            const types = component.types;

                            if (types.includes("subpremise")) fields.unit.value = val;
                            if (types.includes("street_number")) fields.number.value = val;
                            if (types.includes("route")) fields.street.value = val;
                            if (types.includes("locality")) fields.suburb.value = val;
                            if (types.includes("administrative_area_level_1")) fields.state.value = shortVal;
                            if (types.includes("postal_code")) fields.postcode.value = val;
                        }
                    }
                    
                    // Reveal manual fields for user verification
                    const manualPanel = document.getElementById('manual_' + prefix);
                    if (manualPanel) manualPanel.classList.add('open');

                } catch (error) {
                    console.error("Address Debug: Error during fetchFields", error);
                }
            });

            // Handle potential component errors
            autocomplete.addEventListener("gmp-error", (e) => {
                console.error("Address Debug: Autocomplete UI Error", e);
            });

            // 5. Finalize the Swap
            // If the original input had a value, show the manual entry fields immediately
            if (originalInput && originalInput.value.trim() !== '') {
               const manualPanel = document.getElementById('manual_' + prefix);
                if (manualPanel) {
                    manualPanel.classList.add('open');
                    console.log(`Address Debug: Existing data detected, opening manual fields for ${prefix}`);
                }
            }
            
            // Replace the dummy input with the live Google Component
            const displayString = originalInput.dataset.displayString;
             if (displayString && displayString.trim() !== '') {
                const manualPanel = document.getElementById('manual_' + prefix);
                 if (manualPanel) {
                    manualPanel.classList.add('open');
                    console.log(`Address Debug: displayString exists, opening manual fields for ${prefix}`);
                }
            }
           

            originalInput.replaceWith(autocomplete);
            console.log(`Address Debug: Autocomplete successfully attached for ${prefix}`);
        });

    } catch (err) {
        console.error("Address Debug: Setup failed critically", err);
    }
}