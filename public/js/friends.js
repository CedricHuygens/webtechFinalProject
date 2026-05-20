// delete friend
document.addEventListener('click', function(e){
    let button = e.target.closest('.deleteFriendButton'); // closest geeft eerste selector die je tegenkomt in de DOM waarop je klikt

    if (!button){
        return;
    }


    const confirmed = confirm('Are you sure you want to delete this friend?');
    if (!confirmed) {
        return;
    }


    let userId = button.dataset.userId;

    button.innerText = 'Deleting...';

    const base = window.location.pathname.startsWith('/public') ? '/public' : '';

    fetch(`${base}/deleteFriend`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ userId: userId })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success){
                button.closest('div').remove(); // verwijder uit UI
            } else {
                button.innerText = 'Error';
                console.log(data.error);
            }
        })
        .catch(err => console.error(err));
});
