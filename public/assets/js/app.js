// assets/js/app.js
document.addEventListener('DOMContentLoaded', () => {
  const sidebar = document.getElementById('sidebar');
  const toggleMobile = document.getElementById('sidebarToggleMobile');
  if (toggleMobile && sidebar) {
    toggleMobile.addEventListener('click', () => {
      const isHidden = sidebar.style.display === 'none' || getComputedStyle(sidebar).display === 'none';
      sidebar.style.display = isHidden ? 'block' : 'none';
    });
  }
});

// Request a share via API
async function requestShare(propertyId, toAgencyId, permissions, note, csrf) {
  try {
    const res = await fetch('/public/api/share.php?action=request', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ property_id: propertyId, to_agency_id: toAgencyId, permissions, note, csrf })
    });
    return await res.json();
  } catch (err) {
    console.error(err);
    return { error: 'network' };
  }
}

// Upload image helper (FormData)
async function uploadImage(file, csrf) {
  const form = new FormData();
  form.append('image', file);
  form.append('csrf', csrf);
  const res = await fetch('/public/api/upload_image.php', {
    method: 'POST',
    body: form
  });
  return res.json();
}

async function addPropertyImage(propertyId, path, alt, csrf) {
  const res = await fetch('/public/api/property_images.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ action:'add', property_id: propertyId, path, alt, csrf })
  });
  return res.json();
}

async function deletePropertyImage(propertyId, imageId, csrf) {
  const res = await fetch('/public/api/property_images.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ action:'delete', property_id: propertyId, image_id: imageId, csrf })
  });
  return res.json();
}

async function listPropertyImages(propertyId, csrf) {
  const url = `/public/api/property_images.php?action=list&property_id=${encodeURIComponent(propertyId)}&csrf=${encodeURIComponent(csrf)}`;
  const res = await fetch(url, { method: 'GET' });
  return res.json();
}
