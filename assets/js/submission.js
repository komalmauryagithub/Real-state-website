/**
 * EstateVista India - Multi-Step Property Submission Wizard
 * Client-side validation, live Indian price formatting, image previews,
 * and step navigation.
 */

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('propertySubmissionForm');
    if (!form) return;

    let currentStep = 1;
    const totalSteps = 6;

    // Elements
    const stepPanes = document.querySelectorAll('.step-pane');
    const stepperSteps = document.querySelectorAll('.stepper-step');
    const progressFill = document.getElementById('stepperProgressFill');
    const btnPrev = document.getElementById('btnStepPrev');
    const btnNext = document.getElementById('btnStepNext');
    const btnSubmit = document.getElementById('btnStepSubmit');
    const priceInput = document.getElementById('propPriceInput');
    const pricePreview = document.getElementById('priceCrorePreview');
    const dropzone = document.getElementById('photoDropzone');
    const fileInput = document.getElementById('propImagesInput');
    const previewContainer = document.getElementById('photoPreviewsGrid');

    // Store selected files
    let selectedFiles = [];

    // Initialize Stepper
    updateStepperView();

    // -------------------------------------------------------------------------
    // 1. Navigation Button Click Handlers
    // -------------------------------------------------------------------------
    if (btnNext) {
        btnNext.addEventListener('click', function () {
            if (validateCurrentStep(currentStep)) {
                if (currentStep < totalSteps) {
                    currentStep++;
                    updateStepperView();
                    window.scrollTo({ top: form.offsetTop - 100, behavior: 'smooth' });
                }
            }
        });
    }

    if (btnPrev) {
        btnPrev.addEventListener('click', function () {
            if (currentStep > 1) {
                currentStep--;
                updateStepperView();
                window.scrollTo({ top: form.offsetTop - 100, behavior: 'smooth' });
            }
        });
    }

    // Step step-circle direct clicks (only for previously completed steps)
    stepperSteps.forEach(stepBtn => {
        stepBtn.addEventListener('click', function () {
            const targetStep = parseInt(this.getAttribute('data-step'), 10);
            if (targetStep < currentStep) {
                currentStep = targetStep;
                updateStepperView();
            } else if (targetStep > currentStep) {
                // Verify all intermediate steps
                let canAdvance = true;
                for (let s = currentStep; s < targetStep; s++) {
                    if (!validateCurrentStep(s)) {
                        canAdvance = false;
                        break;
                    }
                }
                if (canAdvance) {
                    currentStep = targetStep;
                    updateStepperView();
                }
            }
        });
    });

    // -------------------------------------------------------------------------
    // 2. Update View State
    // -------------------------------------------------------------------------
    function updateStepperView() {
        // Update panes
        stepPanes.forEach(pane => {
            const stepNum = parseInt(pane.getAttribute('data-step'), 10);
            pane.classList.toggle('active', stepNum === currentStep);
        });

        // Update stepper pills
        stepperSteps.forEach(step => {
            const stepNum = parseInt(step.getAttribute('data-step'), 10);
            step.classList.remove('active', 'completed');
            if (stepNum === currentStep) {
                step.classList.add('active');
            } else if (stepNum < currentStep) {
                step.classList.add('completed');
            }
        });

        // Update progress line
        if (progressFill) {
            const pct = ((currentStep - 1) / (totalSteps - 1)) * 100;
            progressFill.style.width = pct + '%';
        }

        // Update footer buttons
        if (btnPrev) {
            btnPrev.style.display = (currentStep === 1) ? 'none' : 'inline-flex';
        }

        if (btnNext && btnSubmit) {
            if (currentStep === totalSteps) {
                btnNext.style.display = 'none';
                btnSubmit.style.display = 'inline-flex';
                populateReviewSummary();
            } else {
                btnNext.style.display = 'inline-flex';
                btnSubmit.style.display = 'none';
            }
        }
    }

    // -------------------------------------------------------------------------
    // 3. Step Validation
    // -------------------------------------------------------------------------
    function validateCurrentStep(step) {
        clearErrors();
        let valid = true;

        if (step === 1) {
            const title = document.getElementById('propTitle');
            if (!title.value.trim() || title.value.trim().length < 5) {
                showFieldError(title, 'Please enter a property title (at least 5 characters).');
                valid = false;
            }

            const cat = document.getElementById('propCategory');
            if (!cat.value) {
                showFieldError(cat, 'Please select a property category.');
                valid = false;
            }

            const price = document.getElementById('propPriceInput');
            if (!price.value || parseFloat(price.value) <= 0) {
                showFieldError(price, 'Please enter a valid price in INR.');
                valid = false;
            }
        } else if (step === 2) {
            const city = document.getElementById('propCity');
            if (!city.value) {
                showFieldError(city, 'Please select a metropolitan city.');
                valid = false;
            }

            const locality = document.getElementById('propLocality');
            if (!locality.value.trim()) {
                showFieldError(locality, 'Please specify the neighborhood or locality.');
                valid = false;
            }

            const address = document.getElementById('propAddress');
            if (!address.value.trim()) {
                showFieldError(address, 'Please provide the street address or landmark.');
                valid = false;
            }
        } else if (step === 3) {
            const bhk = document.getElementById('propBhk');
            if (!bhk.value || parseInt(bhk.value, 10) < 1) {
                showFieldError(bhk, 'Please enter bedroom count (BHK).');
                valid = false;
            }

            const baths = document.getElementById('propBathrooms');
            if (!baths.value || parseInt(baths.value, 10) < 1) {
                showFieldError(baths, 'Please specify number of bathrooms.');
                valid = false;
            }

            const area = document.getElementById('propCarpetArea');
            if (!area.value || parseInt(area.value, 10) <= 0) {
                showFieldError(area, 'Please specify carpet area in sq.ft.');
                valid = false;
            }
        } else if (step === 4) {
            const desc = document.getElementById('propDescription');
            if (!desc.value.trim() || desc.value.trim().length < 20) {
                showFieldError(desc, 'Please provide a descriptive overview (at least 20 characters).');
                valid = false;
            }
        } else if (step === 5) {
            // Images step - check if new submission has images or if existing images present in edit mode
            const existingImages = document.querySelectorAll('.existing-photo-item');
            if (selectedFiles.length === 0 && existingImages.length === 0) {
                const note = document.getElementById('imageStepNotice');
                if (note) {
                    note.style.display = 'block';
                    note.textContent = 'Recommendation: Uploading authentic high-resolution images speeds up curation approval.';
                }
            }
        } else if (step === 6) {
            const contactName = document.getElementById('propContactName');
            if (!contactName.value.trim()) {
                showFieldError(contactName, 'Please enter the contact person name.');
                valid = false;
            }

            const contactPhone = document.getElementById('propContactPhone');
            if (!contactPhone.value.trim() || contactPhone.value.trim().length < 8) {
                showFieldError(contactPhone, 'Please provide a valid Indian contact telephone number.');
                valid = false;
            }

            const terms = document.getElementById('agreeCurationTerms');
            if (terms && !terms.checked) {
                alert('Please verify and acknowledge the EstateVista Curation and Title Authenticity terms.');
                valid = false;
            }
        }

        return valid;
    }

    function showFieldError(input, msg) {
        input.style.borderColor = '#EF4444';
        input.style.backgroundColor = '#FEF2F2';
        let errorSpan = input.parentNode.querySelector('.field-error-msg');
        if (!errorSpan) {
            errorSpan = document.createElement('div');
            errorSpan.className = 'field-error-msg';
            errorSpan.style.color = '#EF4444';
            errorSpan.style.fontSize = '0.78rem';
            errorSpan.style.marginTop = '0.35rem';
            errorSpan.style.fontWeight = '600';
            input.parentNode.appendChild(errorSpan);
        }
        errorSpan.textContent = msg;
        input.focus();
    }

    function clearErrors() {
        document.querySelectorAll('.field-error-msg').forEach(el => el.remove());
        document.querySelectorAll('.sub-input, .sub-select, .sub-textarea').forEach(el => {
            el.style.borderColor = '';
            el.style.backgroundColor = '';
        });
    }

    // -------------------------------------------------------------------------
    // 4. Live INR Price Formatter
    // -------------------------------------------------------------------------
    if (priceInput && pricePreview) {
        priceInput.addEventListener('input', function () {
            const val = parseFloat(this.value);
            if (isNaN(val) || val <= 0) {
                pricePreview.innerHTML = '<i class="fa-solid fa-coins"></i> Enter numeric amount in ₹ INR';
                return;
            }

            let formatted = '';
            if (val >= 10000000) {
                const cr = (val / 10000000).toFixed(2);
                formatted = `₹ ${cr} Crore`;
            } else if (val >= 100000) {
                const lk = (val / 100000).toFixed(2);
                formatted = `₹ ${lk} Lakh`;
            } else {
                formatted = `₹ ${val.toLocaleString('en-IN')}`;
            }

            pricePreview.innerHTML = `<i class="fa-solid fa-coins"></i> Formatted Value: <strong>${formatted}</strong>`;
        });
    }

    // -------------------------------------------------------------------------
    // 5. Drag & Drop Image Uploader
    // -------------------------------------------------------------------------
    if (dropzone && fileInput) {
        dropzone.addEventListener('click', () => fileInput.click());

        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.add('dragover');
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.remove('dragover');
            });
        });

        dropzone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleFiles(files);
        });

        fileInput.addEventListener('change', function () {
            handleFiles(this.files);
        });
    }

    function handleFiles(files) {
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        const maxSize = 5 * 1024 * 1024; // 5 MB

        Array.from(files).forEach(file => {
            if (!allowedTypes.includes(file.type)) {
                alert(`File "${file.name}" is not supported. Please select JPEG, PNG, or WebP images.`);
                return;
            }
            if (file.size > maxSize) {
                alert(`File "${file.name}" exceeds the 5MB size limit.`);
                return;
            }

            selectedFiles.push(file);

            // Generate thumbnail
            const reader = new FileReader();
            reader.onload = function (e) {
                renderThumbnail(e.target.result, file.name, selectedFiles.length === 1);
            };
            reader.readAsDataURL(file);
        });

        // Update the actual file input via DataTransfer if supported
        syncFileInput();
    }

    function renderThumbnail(src, name, isCover) {
        if (!previewContainer) return;

        const item = document.createElement('div');
        item.className = 'photo-preview-item';
        item.innerHTML = `
            <img src="${src}" alt="${name}">
            ${isCover ? '<span class="cover-badge"><i class="fa-solid fa-star"></i> Cover Photo</span>' : ''}
            <button type="button" class="remove-photo-btn" title="Remove image">
                <i class="fa-solid fa-xmark"></i>
            </button>
        `;

        item.querySelector('.remove-photo-btn').addEventListener('click', function () {
            item.remove();
            selectedFiles = selectedFiles.filter(f => f.name !== name);
            syncFileInput();
            // Re-assign cover badge to first item if needed
            const first = previewContainer.querySelector('.photo-preview-item');
            if (first && !first.querySelector('.cover-badge')) {
                const badge = document.createElement('span');
                badge.className = 'cover-badge';
                badge.innerHTML = '<i class="fa-solid fa-star"></i> Cover Photo';
                first.appendChild(badge);
            }
        });

        previewContainer.appendChild(item);
    }

    function syncFileInput() {
        if (!fileInput) return;
        try {
            const dataTransfer = new DataTransfer();
            selectedFiles.forEach(file => dataTransfer.items.add(file));
            fileInput.files = dataTransfer.files;
        } catch (e) {
            // Older browser fallback
        }
    }

    // -------------------------------------------------------------------------
    // 6. Review Summary Table Generation
    // -------------------------------------------------------------------------
    function populateReviewSummary() {
        const revTitle = document.getElementById('revTitle');
        const revCategory = document.getElementById('revCategory');
        const revPurpose = document.getElementById('revPurpose');
        const revPrice = document.getElementById('revPrice');
        const revLocation = document.getElementById('revLocation');
        const revSpecs = document.getElementById('revSpecs');
        const revFurnishing = document.getElementById('revFurnishing');
        const revRera = document.getElementById('revRera');
        const revContact = document.getElementById('revContact');

        if (revTitle) revTitle.textContent = document.getElementById('propTitle').value || '—';
        if (revCategory) {
            const catSelect = document.getElementById('propCategory');
            revCategory.textContent = catSelect.options[catSelect.selectedIndex]?.text || '—';
        }
        if (revPurpose) {
            const checkedPurp = document.querySelector('input[name="purpose"]:checked');
            revPurpose.textContent = checkedPurp ? checkedPurp.value.toUpperCase() : 'BUY';
        }
        if (revPrice) {
            const rawPrice = parseFloat(document.getElementById('propPriceInput').value);
            if (!isNaN(rawPrice)) {
                if (rawPrice >= 10000000) {
                    revPrice.textContent = `₹ ${(rawPrice / 10000000).toFixed(2)} Cr`;
                } else if (rawPrice >= 100000) {
                    revPrice.textContent = `₹ ${(rawPrice / 100000).toFixed(2)} Lakh`;
                } else {
                    revPrice.textContent = `₹ ${rawPrice.toLocaleString('en-IN')}`;
                }
            } else {
                revPrice.textContent = '—';
            }
        }
        if (revLocation) {
            const citySelect = document.getElementById('propCity');
            const cityName = citySelect.options[citySelect.selectedIndex]?.text || '';
            const locName = document.getElementById('propLocality').value || '';
            revLocation.textContent = `${locName}, ${cityName}`;
        }
        if (revSpecs) {
            const bhk = document.getElementById('propBhk').value || '—';
            const baths = document.getElementById('propBathrooms').value || '—';
            const area = document.getElementById('propCarpetArea').value || '—';
            revSpecs.textContent = `${bhk} BHK • ${baths} Baths • ${area} sq.ft Carpet`;
        }
        if (revFurnishing) {
            const furn = document.querySelector('input[name="furnishing"]:checked');
            revFurnishing.textContent = furn ? furn.value.replace('-', ' ').toUpperCase() : 'BESPOKE FURNISHED';
        }
        if (revRera) {
            const reraVal = document.getElementById('propRera').value;
            revRera.textContent = reraVal ? reraVal : 'Pending Verification';
        }
        if (revContact) {
            const name = document.getElementById('propContactName').value;
            const phone = document.getElementById('propContactPhone').value;
            revContact.textContent = `${name} (${phone})`;
        }
    }

    // Form submit guard
    if (form) {
        form.addEventListener('submit', function (e) {
            if (!validateCurrentStep(6)) {
                e.preventDefault();
                return;
            }
            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Transmitting Dossier...';
            }
        });
    }
});
