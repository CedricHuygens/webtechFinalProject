// dynamically get users while typing
document.getElementById('searchBarFriends').addEventListener('input', function (){
    let query = this.value; // what the user currently typed

    fetch(searchFriendsUrl + '?q=' + encodeURIComponent(query))
        .then(res => res.json())
        .then(data => {
            document.getElementById('searchResults').innerHTML = data.html; // sets the html to what is returned in the searchFriends function (with renderView which returnes html, not full page)
        })
})

// add friend button
document.addEventListener('click', function(e){ // e is the event obejct (contains information on what you clicked and stuff)
    let button = e.target.closest('.addFriendButton');

    if (!button){
        return;
    }

    let userId = button.dataset.userId;

    button.innerText = 'Sending...';

    fetch(sendFriendRequestUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ receiverUserId: userId })
    })
        .then(res => res.json())
        .then(data => {
            if(data.success){
                button.innerText = 'Request sent'
                button.disabled = true;
            }
            else{
                button.innerText = 'Error'
                console.log(data.error);
            }
        })
        .catch(err => console.error(err));
});

// cancel sent friend request + delete received friend request
document.addEventListener('click', function(e){
    let button = e.target.closest('.cancelFriendRequestSentButton, .deleteFriendRequestReceivedButton'); // closest geeft eerste selector die je tegenkomt in de DOM waarop je klikt

    if (!button){
        return;
    }

    let requestId = button.dataset.requestId;

    button.innerText = 'Cancelling...';

    fetch(cancelFriendRequestUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ requestId: requestId })
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

// accept friend request
document.addEventListener('click', function(e){
    let button = e.target.closest('.acceptFriendRequestReceivedButton'); // closest geeft eerste selector die je tegenkomt in de DOM waarop je klikt

    if (!button){
        return;
    }

    let requestId = button.dataset.requestId;

    button.innerText = 'Accepting...';

    fetch(acceptFriendRequestUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ requestId: requestId })
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
