document.addEventListener('DOMContentLoaded', () => {
  const tableBody = document.getElementById('usersTableBody');
  const emptyState = document.getElementById('adminEmptyState');
  const pageLabel = document.getElementById('adminPageLabel');
  const prevBtn = document.getElementById('adminPrevBtn');
  const nextBtn = document.getElementById('adminNextBtn');
  const csrfToken = document.getElementById('adminCsrfToken')?.value || '';

  let currentPage = 1;
  let isLastPage = false;

  const fetchUsers = async () => {
    try {
      const params = new URLSearchParams({ page: String(currentPage), limit: '10' });
      const response = await fetch(`/api/show_users?${params.toString()}`);
      const result = await response.json();

      if (!response.ok || result.status !== 'success') {
        throw new Error(result.message || 'Unable to load users.');
      }

      isLastPage = Boolean(result['last-page']);
      renderUsers(result.data || []);
      pageLabel.textContent = `Page ${currentPage}`;
      prevBtn.disabled = currentPage === 1;
      nextBtn.disabled = isLastPage;
    } catch (error) {
      showToast('error', error.message || 'Unable to load users.');
    }
  };

  const renderUsers = (users) => {
    tableBody.innerHTML = '';
    emptyState.classList.toggle('hidden', users.length > 0);

    users.forEach((user) => {
      const row = document.createElement('tr');

      row.innerHTML = `
        <td>${user.id}</td>
        <td>${escapeHtml(user.username)}</td>
        <td><span class="access-pill ${user.role === 'pro' ? 'access-premium' : 'access-free'}">${user.role === 'pro' ? 'Pro' : 'Free'}</span></td>
        <td>
          <label class="sr-only" for="role-select-${user.id}">Change role for ${escapeHtml(user.username)}</label>
          <select class="role-select" id="role-select-${user.id}" data-id="${user.id}" data-role="${user.role}">
            <option value="free" ${user.role === 'free' ? 'selected' : ''}>Free</option>
            <option value="pro" ${user.role === 'pro' ? 'selected' : ''}>Pro</option>
          </select>
        </td>
      `;

      tableBody.appendChild(row);
    });

    tableBody.querySelectorAll('.role-select').forEach((select) => {
      select.addEventListener('change', () => {
        const actualRole = select.dataset.role;
        const newRole = select.value;

        if (actualRole === newRole) {
          return;
        }

        changeRole(select.dataset.id, actualRole, newRole, select);
      });
    });
  };

  const changeRole = async (id, actualRole, newRole, selectElement) => {
    try {
      if (selectElement) {
        selectElement.disabled = true;
      }

      const response = await fetch('/api/change_role', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          id,
          actual_role: actualRole,
          new_role: newRole,
          csrf_token: csrfToken
        })
      });
      const result = await response.json();

      if (!response.ok || result.status !== 'success') {
        throw new Error(result.message || 'Role change failed.');
      }

      showToast('success', result.message || 'Role changed successfully.');
      fetchUsers();
    } catch (error) {
      if (selectElement) {
        selectElement.value = actualRole;
      }
      showToast('error', error.message || 'Role change failed.');
    } finally {
      if (selectElement) {
        selectElement.disabled = false;
      }
    }
  };

  const escapeHtml = (value) => {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
  };

  prevBtn?.addEventListener('click', () => {
    if (currentPage > 1) {
      currentPage -= 1;
      fetchUsers();
    }
  });

  nextBtn?.addEventListener('click', () => {
    if (!isLastPage) {
      currentPage += 1;
      fetchUsers();
    }
  });

  fetchUsers();
});
