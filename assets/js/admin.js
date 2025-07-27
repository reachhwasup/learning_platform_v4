/**
 * Admin Panel JavaScript
 *
 * Handles dynamic interactions for the admin interface, such as modals and AJAX calls for CRUD operations.
 */
document.addEventListener('DOMContentLoaded', () => {
    // --- Module Management ---
    const moduleModal = document.getElementById('module-modal');
    if (moduleModal) {
        const addBtn = document.getElementById('add-module-btn');
        const cancelBtn = document.getElementById('cancel-btn');
        const form = document.getElementById('module-form');
        const feedbackDiv = document.getElementById('form-feedback');

        const openModal = () => moduleModal.classList.remove('hidden');
        const closeModal = () => {
            moduleModal.classList.add('hidden');
            form.reset();
            feedbackDiv.textContent = '';
            document.getElementById('current-pdf').textContent = '';
        };

        addBtn.addEventListener('click', () => {
            form.reset();
            document.getElementById('modal-title').textContent = 'Add New Module';
            document.getElementById('form-action').value = 'add_module';
            document.getElementById('module_id').value = '';
            openModal();
        });

        cancelBtn.addEventListener('click', closeModal);

        form.addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(form);
            
            fetch('../api/admin/module_crud.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal();
                    location.reload(); 
                } else {
                    feedbackDiv.textContent = data.message || 'An error occurred.';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                feedbackDiv.textContent = 'A network or server error occurred.';
            });
        });
    }

    // --- User Management ---
    const userModal = document.getElementById('user-modal');
    if (userModal) {
        const addBtn = document.getElementById('add-user-btn');
        const cancelBtn = document.getElementById('user-cancel-btn');
        const form = document.getElementById('user-form');
        const feedbackDiv = document.getElementById('user-form-feedback');
        const addUserFields = document.getElementById('add-user-fields');
        const userInfoReadonly = document.getElementById('user-info-readonly');

        const openUserModal = () => userModal.classList.remove('hidden');
        const closeUserModal = () => {
            userModal.classList.add('hidden');
            form.reset();
            feedbackDiv.textContent = '';
        };

        addBtn.addEventListener('click', () => {
            form.reset();
            document.getElementById('user-modal-title').textContent = 'Add New User';
            document.getElementById('user-form-action').value = 'add_user';
            document.getElementById('user_id').value = '';
            addUserFields.style.display = 'block';
            userInfoReadonly.style.display = 'none';
            openUserModal();
        });

        cancelBtn.addEventListener('click', closeUserModal);

        form.addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(form);

            fetch('../api/admin/user_crud.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeUserModal();
                    location.reload();
                } else {
                    feedbackDiv.textContent = data.message || 'An error occurred.';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                feedbackDiv.textContent = 'A network or server error occurred.';
            });
        });
    }

    // --- Question Management ---
    const questionModal = document.getElementById('question-modal');
    if (questionModal) {
        const addBtn = document.getElementById('add-question-btn');
        const cancelBtn = document.getElementById('question-cancel-btn');
        const form = document.getElementById('question-form');
        const feedbackDiv = document.getElementById('question-form-feedback');
        const optionsContainer = document.getElementById('options-container');
        const addOptionBtn = document.getElementById('add-option-btn');
        const associationType = document.getElementById('association_type');
        const moduleSelectContainer = document.getElementById('module-select-container');
        const importForm = document.getElementById('import-form');
        const importFeedback = document.getElementById('import-feedback');
        const deleteAllBtn = document.getElementById('delete-all-questions-btn');

        const openQuestionModal = () => questionModal.classList.remove('hidden');
        const closeQuestionModal = () => {
            questionModal.classList.add('hidden');
            form.reset();
            optionsContainer.innerHTML = ''; // Clear dynamic options
            feedbackDiv.textContent = '';
        };

        const createOptionInput = (option = {}, index = 0) => {
            const inputType = document.getElementById('question_type').value === 'single' ? 'radio' : 'checkbox';
            const optionDiv = document.createElement('div');
            optionDiv.className = 'flex items-center space-x-2';
            optionDiv.innerHTML = `
                <input type="${inputType}" name="options[${index}][is_correct]" class="h-5 w-5 text-primary focus:ring-primary border-gray-300" ${option.is_correct ? 'checked' : ''}>
                <input type="text" name="options[${index}][text]" value="${option.text || ''}" placeholder="Option text" required class="flex-grow rounded-md border-gray-300 shadow-sm">
                <input type="hidden" name="options[${index}][id]" value="${option.id || ''}">
                <button type="button" class="remove-option-btn text-red-500 hover:text-red-700">&times;</button>
            `;
            return optionDiv;
        };
        
        const renderOptions = (options = []) => {
            optionsContainer.innerHTML = '';
            let initialOptions = options.length > 0 ? options : [{}, {}]; // Start with 2 empty options if new
            initialOptions.forEach((opt, i) => optionsContainer.appendChild(createOptionInput(opt, i)));
        };

        addOptionBtn.addEventListener('click', () => {
            const index = optionsContainer.children.length;
            optionsContainer.appendChild(createOptionInput({}, index));
        });
        
        optionsContainer.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-option-btn')) {
                e.target.parentElement.remove();
            }
        });

        associationType.addEventListener('change', () => {
            moduleSelectContainer.style.display = associationType.value === 'module' ? 'block' : 'none';
        });

        document.getElementById('question_type').addEventListener('change', () => renderOptions());

        addBtn.addEventListener('click', () => {
            form.reset();
            document.getElementById('question-modal-title').textContent = 'Add New Question';
            document.getElementById('question-form-action').value = 'add_question';
            document.getElementById('question_id').value = '';
            associationType.dispatchEvent(new Event('change'));
            renderOptions();
            openQuestionModal();
        });

        cancelBtn.addEventListener('click', closeQuestionModal);

        form.addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(form);
            fetch('../api/admin/question_crud.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeQuestionModal();
                    location.reload();
                } else {
                    feedbackDiv.textContent = data.message || 'An error occurred.';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                feedbackDiv.textContent = 'A network or server error occurred.';
            });
        });

        importForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(importForm);
            formData.append('action', 'import_questions');
            importFeedback.textContent = 'Importing...';
            importFeedback.className = 'mt-2 text-sm text-blue-600';

            fetch('../api/admin/question_crud.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                importFeedback.textContent = data.message;
                importFeedback.className = `mt-2 text-sm ${data.success ? 'text-green-600' : 'text-red-600'}`;
                if (data.success) {
                    setTimeout(() => location.reload(), 2000);
                }
            })
            .catch(error => {
                importFeedback.textContent = 'A network error occurred.';
                importFeedback.className = 'mt-2 text-sm text-red-600';
            });
        });

        deleteAllBtn.addEventListener('click', function() {
            if (!confirm('Are you absolutely sure you want to delete ALL questions from the database? This action cannot be undone.')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'delete_all_questions');

            fetch('../api/admin/question_crud.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Failed to delete questions: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('A network or server error occurred.');
            });
        });
    }
});

// --- Global Functions ---

function editModule(id) {
    const modal = document.getElementById('module-modal');
    fetch(`../api/admin/module_crud.php?action=get_module&id=${id}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const module = data.data;
            document.getElementById('modal-title').textContent = 'Edit Module';
            document.getElementById('form-action').value = 'edit_module';
            document.getElementById('module_id').value = module.id;
            document.getElementById('title').value = module.title;
            document.getElementById('description').value = module.description;
            document.getElementById('module_order').value = module.module_order;
            
            const currentPdf = document.getElementById('current-pdf');
            if (module.pdf_material_path) {
                currentPdf.innerHTML = `Current file: <a href="../uploads/materials/${module.pdf_material_path}" target="_blank" class="text-indigo-600 hover:underline">${module.pdf_material_path}</a>`;
            } else {
                currentPdf.textContent = 'No PDF material uploaded.';
            }
            modal.classList.remove('hidden');
        } else { alert(data.message); }
    })
    .catch(error => console.error('Error fetching module data:', error));
}

function deleteModule(id) {
    if (!confirm('Are you sure you want to delete this module? This will also delete all associated videos and questions. This action cannot be undone.')) {
        return;
    }
    const formData = new FormData();
    formData.append('action', 'delete_module');
    formData.append('module_id', id);
    fetch('../api/admin/module_crud.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const row = document.getElementById(`module-row-${id}`);
            if (row) row.remove();
        } else { alert('Failed to delete module: ' + data.message); }
    })
    .catch(error => { console.error('Error:', error); alert('A network or server error occurred.'); });
}

function editUser(id) {
    const modal = document.getElementById('user-modal');
    fetch(`../api/admin/user_crud.php?action=get_user&id=${id}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const user = data.data;
            document.getElementById('user-modal-title').textContent = 'Edit User';
            document.getElementById('user-form-action').value = 'edit_user';
            document.getElementById('user_id').value = user.id;
            document.getElementById('role').value = user.role;
            document.getElementById('status').value = user.status;

            const userInfoDiv = document.getElementById('user-info-readonly');
            userInfoDiv.innerHTML = `
                <p><strong class="font-medium text-gray-700">Name:</strong> ${escapeHTML(user.first_name)} ${escapeHTML(user.last_name)}</p>
                <p><strong class="font-medium text-gray-700">Email:</strong> ${escapeHTML(user.email)}</p>
                <p><strong class="font-medium text-gray-700">Staff ID:</strong> ${escapeHTML(user.staff_id)}</p>
            `;
            userInfoDiv.style.display = 'block';
            document.getElementById('add-user-fields').style.display = 'none';

            modal.classList.remove('hidden');
        } else {
            alert(data.message);
        }
    })
    .catch(error => console.error('Error fetching user data:', error));
}

function deleteUser(id) {
    if (!confirm('Are you sure you want to delete this user? All their progress and assessment data will be lost permanently.')) {
        return;
    }
    const formData = new FormData();
    formData.append('action', 'delete_user');
    formData.append('user_id', id);
    fetch('../api/admin/user_crud.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const row = document.getElementById(`user-row-${id}`);
            if (row) row.remove();
        } else { alert('Failed to delete user: ' + data.message); }
    })
    .catch(error => { console.error('Error:', error); alert('A network or server error occurred.'); });
}

function resetPassword(userId) {
    if (!confirm('Are you sure you want to reset the password for this user? A new temporary password will be generated.')) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'reset_password');
    formData.append('user_id', userId);

    fetch('../api/admin/user_crud.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.prompt(
                "Password has been reset successfully. Please provide this temporary password to the user.\n\nNew Password:", 
                data.new_password
            );
        } else {
            alert('Failed to reset password: ' + (data.message || 'Unknown error.'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('A network or server error occurred during the password reset.');
    });
}

window.editQuestion = function(id) {
    fetch(`../api/admin/question_crud.php?action=get_question&id=${id}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const q = data.data;
            document.getElementById('question-modal-title').textContent = 'Edit Question';
            document.getElementById('question-form-action').value = 'edit_question';
            document.getElementById('question_id').value = q.id;
            document.getElementById('question_text').value = q.question_text;
            document.getElementById('question_type').value = q.question_type;
            
            const assocType = document.getElementById('association_type');
            assocType.value = q.is_final_exam_question == 1 ? 'final_exam' : 'module';
            assocType.dispatchEvent(new Event('change'));

            if (q.is_final_exam_question != 1) {
                document.getElementById('module_id').value = q.module_id;
            }
            
            const optionsContainer = document.getElementById('options-container');
            optionsContainer.innerHTML = '';
            q.options.forEach((opt, i) => {
                const inputType = q.question_type === 'single' ? 'radio' : 'checkbox';
                const optionDiv = document.createElement('div');
                optionDiv.className = 'flex items-center space-x-2';
                optionDiv.innerHTML = `
                    <input type="${inputType}" name="options[${i}][is_correct]" class="h-5 w-5 text-primary focus:ring-primary border-gray-300" ${opt.is_correct == 1 ? 'checked' : ''}>
                    <input type="text" name="options[${i}][text]" value="${escapeHTML(opt.option_text)}" required class="flex-grow rounded-md border-gray-300 shadow-sm">
                `;
                optionsContainer.appendChild(optionDiv);
            });

            document.getElementById('question-modal').classList.remove('hidden');
        } else { alert(data.message); }
    });
}

window.deleteQuestion = function(id) {
    if (!confirm('Are you sure you want to delete this question?')) return;
    const formData = new FormData();
    formData.append('action', 'delete_question');
    formData.append('question_id', id);
    fetch('../api/admin/question_crud.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById(`question-row-${id}`).remove();
        } else { alert(data.message); }
    });
}

function escapeHTML(str) {
    if (str === null || str === undefined) return '';
    var p = document.createElement("p");
    p.appendChild(document.createTextNode(str));
    return p.innerHTML;
}
