/**
 * WordPress QR Code Generator Pro - Admin JavaScript
 * 
 * Professional admin interface JavaScript for seamless user experience.
 * Handles form interactions, AJAX operations, and user feedback.
 * 
 * @package WP_QR_Generator_Pro
 * @subpackage Admin/Assets
 * @author Bruno Brottes <contact@brunobrottes.com>
 * @copyright 2024 Bruno Brottes
 * @license CodeCanyon Regular License
 * @version 1.0.0
 * @since 1.0.0
 * 
 * Key Features:
 * - Dynamic form field switching based on QR type selection
 * - AJAX-powered QR code generation with real-time feedback
 * - Professional UI feedback with loading states and error handling
 * - PDF download integration with user-friendly notifications
 * - Form validation and user guidance
 * - Mobile-responsive interaction handling
 * 
 * Functionality Overview:
 * 1. QR Type Selection: Dynamically shows/hides relevant form fields
 * 2. Form Validation: Client-side validation before submission
 * 3. AJAX Submission: Seamless form submission without page reload
 * 4. Progress Feedback: Visual indicators during generation process
 * 5. Result Display: Shows generated QR codes with download options
 * 6. Error Handling: User-friendly error messages and recovery
 * 
 * Dependencies:
 * - jQuery (included with WordPress)
 * - WordPress AJAX infrastructure
 * - wpQRGenerator object (localized from PHP)
 */

jQuery(document).ready(function($) {
    console.log('[WP_QR_Generator] Enhanced admin script initialized.');

    // Check if we are on the QR Generator page
    const qrForm = $('#qr-generator-form');
    if (qrForm.length === 0) {
        return;
    }

    const typeSelect = $('#qr-type');
    const productRow = $('#row-product-id');
    const categoryRow = $('#row-category-id');
    const customRow = $('#row-custom-data');
    const feedback = $('#qr-feedback');
    const result = $('#qr-result');
    const actions = $('#qr-actions');
    const downloadLink = $('#qr-download-link');
    const testLink = $('#qr-test-link');
    const previewLink = $('#qr-preview-link');

    // Function to toggle form fields based on selected QR code type
    function toggleQrTypeFields() {
        const selectedType = typeSelect.val();
        console.log('[WP_QR_Generator] QR Type changed to:', selectedType);
        
        // Hide all rows first
        productRow.hide();
        categoryRow.hide();
        customRow.hide();
        
        // Show the appropriate row
        switch(selectedType) {
            case 'product':
                productRow.show();
                break;
            case 'category':
                categoryRow.show();
                break;
            case 'shop':
                // No additional fields needed for shop page - it auto-detects the shop URL
                break;
            case 'custom':
                customRow.show();
                break;
        }
    }

    // Bind the event listener
    typeSelect.on('change', toggleQrTypeFields);
    
    // Trigger the change on page load to set the initial state
    toggleQrTypeFields();

    // Handle the form submission
    qrForm.on('submit', function(e) {
        e.preventDefault();
        console.log('[WP_QR_Generator] Form submission triggered.');

        // Validate form based on selected type
        const selectedType = typeSelect.val();
        let validationError = '';
        
        switch(selectedType) {
            case 'product':
                if (!$('#product-id').val()) {
                    validationError = 'Please select a product.';
                }
                break;
            case 'category':
                if (!$('#category-id').val()) {
                    validationError = 'Please select a category.';
                }
                break;
            case 'custom':
                if (!$('#custom-data').val().trim()) {
                    validationError = 'Please enter custom data.';
                }
                break;
            // 'shop' doesn't need validation
        }
        
        if (validationError) {
            feedback.show().text('Error: ' + validationError).css('color', 'red');
            return;
        }

        feedback.show().text('Generating QR code template...').css('color', '#666');
        result.html('');
        actions.hide();

        // Collect all form data including template options
        const formData = new FormData();
        
        // Basic QR data
        formData.append('action', 'generate_qr_code');
        formData.append('nonce', wpQRGenerator.nonce);
        formData.append('qr_type', selectedType);
        
        // Type-specific data
        if (selectedType === 'product') {
            formData.append('product_id', $('#product-id').val());
        } else if (selectedType === 'category') {
            formData.append('category_id', $('#category-id').val());
        } else if (selectedType === 'custom') {
            formData.append('custom_data', $('#custom-data').val());
        }
        
        // Template options
        formData.append('include_header', $('#include-header').is(':checked') ? '1' : '0');
        formData.append('include_footer', $('#include-footer').is(':checked') ? '1' : '0');
        formData.append('include_navigation', $('#include-navigation').is(':checked') ? '1' : '0');
        formData.append('include_sidebar', $('#include-sidebar').is(':checked') ? '1' : '0');
        formData.append('qr_position', 'center'); // Default position since dropdown was removed
        formData.append('qr_size', $('#qr-size').val());

        console.log('[WP_QR_Generator] Sending enhanced AJAX request with template options');

        $.ajax({
            url: wpQRGenerator.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('[WP_QR_Generator] AJAX Success Response:', response);
                if (response.success) {
                    feedback.hide();
                    
                    // Show preview of QR code or PDF message
                    if (response.data.pdf_url) {
                        result.html('<div style="text-align: center; padding: 20px; border: 2px dashed #2271b1; border-radius: 8px; background: #f6f7f7;"><p style="font-size: 16px; margin: 10px 0;"><strong>PDF Generated Successfully!</strong></p><p>Your QR code template has been created as a professional PDF document.</p><p style="margin-top: 15px;"><a href="' + response.data.pdf_url + '" class="button button-primary" target="_blank">📄 View PDF</a></p></div>');
                    } else {
                        result.html('<img src="' + response.data.file_url + '" alt="Generated QR Code Template" style="max-width: 100%; height: auto;" />');
                    }
                    
                    // Set download link - prioritize PDF over image
                    if (response.data.pdf_url) {
                        downloadLink.attr('href', response.data.pdf_url).text('📄 Download PDF');
                    } else {
                        downloadLink.attr('href', response.data.file_url).text('📥 Download QR Code');
                    }
                    
                    testLink.attr('href', response.data.url);
                    
                    // Set preview link if template URL is provided
                    if (response.data.template_url) {
                        previewLink.attr('href', response.data.template_url).show();
                    } else {
                        previewLink.hide();
                    }
                    
                    actions.show();
                    
                } else {
                    feedback.text('Error: ' + (response.data.message || 'An unknown error occurred.')).css('color', 'red');
                    result.html('');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('[WP_QR_Generator] AJAX Error:', textStatus, errorThrown, jqXHR.responseText);
                feedback.text('A critical server or network error occurred. Check the browser console for more details.').css('color', 'red');
                result.html('');
                actions.hide();
            }
        });
    });
}); 