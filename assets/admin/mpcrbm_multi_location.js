jQuery(document).ready(function($) {
    
    // Multi-location toggle functionality
    $('.mpcrbm-toggle-multi-location').on('change', function() {
        var isChecked = $(this).is(':checked');
        var configContainer = $('#mpcrbm-multi-location-config');
        
        if (isChecked) {
            configContainer.slideDown(300);
        } else {
            configContainer.slideUp(300);
        }
    });
    
    // Add new location price row
    $('#mpcrbm-add-location-price').on('click', function() {
        var container = $('#mpcrbm-location-prices-container');
        var existingRows = container.find('.mpcrbm-location-price-row');
        var newIndex = existingRows.length;
        
        // Clone the first row as template
        var template = existingRows.first().clone();
        
        // Clear the values
        template.find('select, input').val('');
        
        // Update the index
        template.attr('data-index', newIndex);
        template.find('select, input').each(function() {
            var name = $(this).attr('name');
            if (name) {
                var newName = name.replace(/\[\d+\]/, '[' + newIndex + ']');
                $(this).attr('name', newName);
            }
        });
        
        // Add the new row
        container.append(template);
        
        // Animate the new row
        template.hide().slideDown(300);
    });
    
    // Remove location price row
    $(document).on('click', '.mpcrbm-remove-location-price', function() {
        var row = $(this).closest('.mpcrbm-location-price-row');
        var container = $('#mpcrbm-location-prices-container');
        var totalRows = container.find('.mpcrbm-location-price-row').length;
        
        // Don't remove if it's the last row
        if (totalRows <= 1) {
            alert('At least one location price configuration is required.');
            return;
        }
        
        // Confirm deletion
        if (confirm('Are you sure you want to remove this location price configuration?')) {
            row.slideUp(300, function() {
                $(this).remove();
                // Reindex remaining rows
                reindexLocationRows();
            });
        }
    });
    
    // Reindex location price rows
    function reindexLocationRows() {
        $('#mpcrbm-location-prices-container .mpcrbm-location-price-row').each(function(index) {
            $(this).attr('data-index', index);
            $(this).find('select, input').each(function() {
                var name = $(this).attr('name');
                if (name) {
                    var newName = name.replace(/\[\d+\]/, '[' + index + ']');
                    $(this).attr('name', newName);
                }
            });
        });
    }
    
    // Validate location price form
    function validateLocationPrices() {
        var isValid = true;
        var errors = [];
        
        $('#mpcrbm-location-prices-container .mpcrbm-location-price-row').each(function(index) {
            var pickup = $(this).find('select[name*="[pickup_location]"]').val();
            var dropoff = $(this).find('select[name*="[dropoff_location]"]').val();
            var dailyPrice = $(this).find('input[name*="[daily_price]"]').val();
            
            if (!pickup) {
                errors.push('Row ' + (index + 1) + ': Pickup location is required');
                isValid = false;
            }
            
            if (!dropoff) {
                errors.push('Row ' + (index + 1) + ': Dropoff location is required');
                isValid = false;
            }
            
            if (!dailyPrice || dailyPrice <= 0) {
                errors.push('Row ' + (index + 1) + ': Daily price must be greater than 0');
                isValid = false;
            }
            
            // Check for duplicate pickup/dropoff combinations
            var combination = pickup + '-' + dropoff;
            var duplicateCount = 0;
            $('#mpcrbm-location-prices-container .mpcrbm-location-price-row').each(function() {
                var currentPickup = $(this).find('select[name*="[pickup_location]"]').val();
                var currentDropoff = $(this).find('select[name*="[dropoff_location]"]').val();
                if (currentPickup + '-' + currentDropoff === combination) {
                    duplicateCount++;
                }
            });
            
            if (duplicateCount > 1) {
                errors.push('Row ' + (index + 1) + ': Duplicate pickup/dropoff combination');
                isValid = false;
            }
        });
        
        if (!isValid) {
            alert('Please fix the following errors:\n\n' + errors.join('\n'));
        }
        
        return isValid;
    }
    
    // Form submission validation
    $('form').on('submit', function(e) {
        var multiLocationEnabled = $('.mpcrbm-toggle-multi-location').is(':checked');
        
        if (multiLocationEnabled) {
            if (!validateLocationPrices()) {
                e.preventDefault();
                return false;
            }
        }
    });
    
    // Auto-save functionality
    var autoSaveTimer;
    $('.mpcrbm-location-price-row select, .mpcrbm-location-price-row input').on('change', function() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(function() {
            // Show auto-save indicator
            showAutoSaveIndicator();
        }, 1000);
    });
    
    function showAutoSaveIndicator() {
        var indicator = $('<div class="mpcrbm-auto-save-indicator">Auto-saving...</div>');
        indicator.css({
            position: 'fixed',
            top: '20px',
            right: '20px',
            background: '#28a745',
            color: 'white',
            padding: '10px 15px',
            borderRadius: '4px',
            zIndex: 9999,
            fontSize: '12px'
        });
        
        $('body').append(indicator);
        
        setTimeout(function() {
            indicator.fadeOut(300, function() {
                $(this).remove();
            });
        }, 2000);
    }
    
    // Location dependency handling
    $(document).on('change', 'select[name*="[pickup_location]"]', function() {
        var row = $(this).closest('.mpcrbm-location-price-row');
        var pickupLocation = $(this).val();
        var dropoffSelect = row.find('select[name*="[dropoff_location]"]');
        
        // Reset dropoff location when pickup changes
        dropoffSelect.val('');
        
        // You can add AJAX call here to get available dropoff locations
        // based on the selected pickup location
    });
    
    // Price calculation preview
    $(document).on('input', 'input[name*="[daily_price]"], input[name*="[transfer_fee]"]', function() {
        var row = $(this).closest('.mpcrbm-location-price-row');
        var dailyPrice = parseFloat(row.find('input[name*="[daily_price]"]').val()) || 0;
        var transferFee = parseFloat(row.find('input[name*="[transfer_fee]"]').val()) || 0;
        
        // Show price preview (example for 1 day rental)
        var totalPrice = dailyPrice + transferFee;
        var previewElement = row.find('.price-preview');
        
        if (previewElement.length === 0) {
            previewElement = $('<div class="price-preview" style="font-size: 11px; color: #666; margin-top: 5px;"></div>');
            row.find('.mpcrbm-location-field').last().after(previewElement);
        }
        
        if (dailyPrice > 0) {
            previewElement.text('1-day total: $' + totalPrice.toFixed(2));
        } else {
            previewElement.text('');
        }
    });
    
    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl/Cmd + Enter to save
        if ((e.ctrlKey || e.metaKey) && e.keyCode === 13) {
            e.preventDefault();
            $('form').submit();
        }
        
        // Ctrl/Cmd + N to add new location price
        if ((e.ctrlKey || e.metaKey) && e.keyCode === 78) {
            e.preventDefault();
            $('#mpcrbm-add-location-price').click();
        }
    });
    
    // Add helpful titles to form fields
    $('.mpcrbm-location-field label').each(function() {
        var label = $(this);
        var fieldName = label.text().toLowerCase();
        
        // Add helpful titles based on field name
        if (fieldName.includes('pickup')) {
            label.attr('title', 'Select the location where customers will pick up the vehicle');
        } else if (fieldName.includes('dropoff')) {
            label.attr('title', 'Select the location where customers will return the vehicle');
        } else if (fieldName.includes('daily')) {
            label.attr('title', 'Set the daily rental rate for this location combination');
        } else if (fieldName.includes('transfer')) {
            label.attr('title', 'Additional fee for one-way rentals (pickup and dropoff at different locations)');
        }
    });
    
});
