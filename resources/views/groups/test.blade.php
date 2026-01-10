<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Groups Test - Comments & Reactions</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    <style>
        .reaction-btn {
            transition: all 0.2s ease;
        }
        .reaction-btn:hover {
            transform: scale(1.1);
        }
        .reaction-btn.active {
            background-color: #3b82f6;
            color: white;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-center mb-8">Groups Test - Comments & Reactions</h1>
        
        <!-- Auth Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Authentication</h2>
            <div class="flex gap-4">
                <input type="text" id="token" placeholder="Enter Bearer Token (without 'Bearer ' prefix)" class="flex-1 px-3 py-2 border rounded-lg">
                <button onclick="setToken()" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">Set Token</button>
                <button onclick="testAPI()" class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600">Test API</button>
            </div>
            <div id="auth-status" class="mt-2 text-sm"></div>
        </div>

        <!-- Groups Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Groups</h2>
            <button onclick="loadGroups()" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 mb-4">Load Groups</button>
            <div id="groups-list" class="space-y-2"></div>
        </div>

        <!-- Posts Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Posts</h2>
            <div class="flex gap-4 mb-4">
                <button onclick="loadGlobalPosts()" class="bg-purple-500 text-white px-4 py-2 rounded-lg hover:bg-purple-600">Load Global Posts</button>
                <input type="number" id="group-id" placeholder="Group ID" class="px-3 py-2 border rounded-lg">
                <button onclick="loadGroupPosts()" class="bg-indigo-500 text-white px-4 py-2 rounded-lg hover:bg-indigo-600">Load Group Posts</button>
            </div>
            <div id="posts-list" class="space-y-6"></div>
        </div>

        <!-- Create Post Section -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Create Post</h2>
            <div class="space-y-4">
                <textarea id="post-content" placeholder="What's on your mind?" class="w-full px-3 py-2 border rounded-lg h-24"></textarea>
                <div class="flex gap-4">
                    <input type="number" id="post-group-id" placeholder="Group ID (optional)" class="px-3 py-2 border rounded-lg">
                    <select id="post-visibility" class="px-3 py-2 border rounded-lg">
                        <option value="public">Public</option>
                        <option value="group_only">Group Only</option>
                        <option value="private">Private</option>
                    </select>
                    <button onclick="createPost()" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">Post</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let authToken = '';
        let currentUserId = null;

        // Setup axios defaults
        axios.defaults.baseURL = '/api';
        axios.defaults.headers.common['Accept'] = 'application/json';
        axios.defaults.headers.common['Content-Type'] = 'application/json';
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function setToken() {
            authToken = document.getElementById('token').value.trim();
            if (authToken) {
                // Ensure token has Bearer prefix
                if (!authToken.startsWith('Bearer ')) {
                    authToken = 'Bearer ' + authToken;
                }
                
                axios.defaults.headers.common['Authorization'] = authToken;
                document.getElementById('auth-status').innerHTML = '<span class="text-blue-600">⏳ Verifying token...</span>';
                
                // Get user info to verify token
                axios.get('/user').then(response => {
                    currentUserId = response.data.id;
                    document.getElementById('auth-status').innerHTML = `<span class="text-green-600">✓ Authenticated as ${response.data.name} (ID: ${response.data.id})</span>`;
                }).catch(error => {
                    console.error('Auth error:', error);
                    document.getElementById('auth-status').innerHTML = `<span class="text-red-600">✗ Invalid token: ${error.response?.data?.message || error.message}</span>`;
                });
            } else {
                document.getElementById('auth-status').innerHTML = '<span class="text-red-600">✗ Please enter a token</span>';
            }
        }

        function loadGroups() {
            axios.get('/groups').then(response => {
                const groupsList = document.getElementById('groups-list');
                groupsList.innerHTML = '';
                
                response.data.data.forEach(group => {
                    const groupDiv = document.createElement('div');
                    groupDiv.className = 'p-3 border rounded-lg';
                    groupDiv.innerHTML = `
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="font-semibold">${group.name}</h3>
                                <p class="text-sm text-gray-600">${group.description || 'No description'}</p>
                                <p class="text-xs text-gray-500">Members: ${group.members_count} | Posts: ${group.posts_count}</p>
                            </div>
                            <button onclick="loadGroupPosts(${group.id})" class="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600">View Posts</button>
                        </div>
                    `;
                    groupsList.appendChild(groupDiv);
                });
            }).catch(error => {
                console.error('Error loading groups:', error);
                alert('Error loading groups. Check console.');
            });
        }

        function loadGlobalPosts() {
            axios.get('/posts').then(response => {
                displayPosts(response.data.data);
            }).catch(error => {
                console.error('Error loading posts:', error);
                alert('Error loading posts. Check console.');
            });
        }

        function loadGroupPosts(groupId = null) {
            const id = groupId || document.getElementById('group-id').value;
            if (!id) {
                alert('Please enter a group ID');
                return;
            }

            axios.get(`/posts/group/${id}`).then(response => {
                displayPosts(response.data.data);
            }).catch(error => {
                console.error('Error loading group posts:', error);
                alert('Error loading group posts. Check console.');
            });
        }

        function displayPosts(posts) {
            const postsList = document.getElementById('posts-list');
            postsList.innerHTML = '';

            posts.forEach(post => {
                const postDiv = document.createElement('div');
                postDiv.className = 'border rounded-lg p-4';
                postDiv.innerHTML = `
                    <div class="mb-3">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                                ${post.user.name.charAt(0).toUpperCase()}
                            </div>
                            <div>
                                <h4 class="font-semibold">${post.user.name}</h4>
                                <p class="text-xs text-gray-500">${new Date(post.created_at).toLocaleString()}</p>
                            </div>
                            ${post.group ? `<span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs ml-auto">${post.group.name}</span>` : ''}
                        </div>
                        <p class="text-gray-800">${post.content || 'No content'}</p>
                        ${post.is_shared ? '<p class="text-sm text-gray-600 italic">Shared post</p>' : ''}
                    </div>

                    <!-- Reactions -->
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-sm text-gray-600">${post.reactions_count} reactions</span>
                        <span class="text-sm text-gray-600">${post.comments_count} comments</span>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-2 mb-4 border-t pt-3">
                        <button onclick="react(${post.id}, 'post', 'like')" class="reaction-btn flex items-center gap-1 px-3 py-1 rounded-lg border hover:bg-gray-50">
                            👍 Like
                        </button>
                        <button onclick="react(${post.id}, 'post', 'love')" class="reaction-btn flex items-center gap-1 px-3 py-1 rounded-lg border hover:bg-gray-50">
                            ❤️ Love
                        </button>
                        <button onclick="react(${post.id}, 'post', 'haha')" class="reaction-btn flex items-center gap-1 px-3 py-1 rounded-lg border hover:bg-gray-50">
                            😂 Haha
                        </button>
                        <button onclick="toggleComments(${post.id})" class="flex items-center gap-1 px-3 py-1 rounded-lg border hover:bg-gray-50">
                            💬 Comment
                        </button>
                        <button onclick="sharePost(${post.id})" class="flex items-center gap-1 px-3 py-1 rounded-lg border hover:bg-gray-50">
                            📤 Share
                        </button>
                    </div>

                    <!-- Reactions Display -->
                    <div id="reactions-${post.id}" class="mb-3"></div>

                    <!-- Comments Section -->
                    <div id="comments-${post.id}" class="hidden">
                        <div class="border-t pt-3">
                            <div class="flex gap-2 mb-3">
                                <input type="text" id="comment-input-${post.id}" placeholder="Write a comment..." class="flex-1 px-3 py-2 border rounded-lg">
                                <button onclick="addComment(${post.id})" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">Post</button>
                            </div>
                            <div id="comments-list-${post.id}" class="space-y-2"></div>
                        </div>
                    </div>
                `;
                postsList.appendChild(postDiv);

                // Load reactions
                loadReactions(post.id, 'post');
                // Load comments
                loadComments(post.id);
            });
        }

        function react(targetId, targetType, reactionType) {
            const payload = {
                target_type: targetType,
                target_id: parseInt(targetId),
                reaction_type: reactionType
            };
            
            console.log('Sending reaction request:', payload);
            
            axios.post('/reactions', payload)
                .then(response => {
                    console.log('Reaction added:', response.data);
                    loadReactions(targetId, targetType);
                    
                    // Refresh posts to update counts
                    if (targetType === 'post') {
                        loadGlobalPosts();
                    }
                })
                .catch(error => {
                    console.error('Error adding reaction:', error);
                    if (error.response) {
                        console.error('Error response:', error.response.data);
                        console.error('Request config:', error.config);
                    }
                    alert('Error: ' + (error.response?.data?.message || error.message));
                });
        }

        function loadReactions(targetId, targetType) {
            axios.get('/reactions', {
                params: {
                    target_type: targetType,
                    target_id: targetId
                }
            }).then(response => {
                const reactionsDiv = document.getElementById(`reactions-${targetId}`);
                if (reactionsDiv && response.data.length > 0) {
                    let reactionsHtml = '<div class="flex gap-2 flex-wrap">';
                    response.data.forEach(reaction => {
                        const emoji = getReactionEmoji(reaction.type);
                        reactionsHtml += `
                            <span class="bg-gray-100 rounded-full px-2 py-1 text-sm flex items-center gap-1">
                                ${emoji} ${reaction.count}
                            </span>
                        `;
                    });
                    reactionsHtml += '</div>';
                    reactionsDiv.innerHTML = reactionsHtml;
                } else if (reactionsDiv) {
                    reactionsDiv.innerHTML = '';
                }
            }).catch(error => {
                console.error('Error loading reactions:', error);
            });
        }

        function getReactionEmoji(type) {
            const emojis = {
                'like': '👍',
                'love': '❤️',
                'haha': '😂',
                'wow': '😮',
                'sad': '😢',
                'angry': '😠'
            };
            return emojis[type] || '👍';
        }

        function toggleComments(postId) {
            const commentsDiv = document.getElementById(`comments-${postId}`);
            if (commentsDiv.classList.contains('hidden')) {
                commentsDiv.classList.remove('hidden');
                loadComments(postId);
            } else {
                commentsDiv.classList.add('hidden');
            }
        }

        function loadComments(postId) {
            axios.get(`/posts/${postId}/comments`).then(response => {
                const commentsList = document.getElementById(`comments-list-${postId}`);
                commentsList.innerHTML = '';

                response.data.forEach(comment => {
                    const commentDiv = document.createElement('div');
                    commentDiv.className = 'bg-gray-50 rounded-lg p-3';
                    commentDiv.innerHTML = `
                        <div class="flex items-start gap-2">
                            <div class="w-6 h-6 bg-gray-300 rounded-full flex items-center justify-center text-xs">
                                ${comment.user.name.charAt(0).toUpperCase()}
                            </div>
                            <div class="flex-1">
                                <div class="bg-white rounded-lg p-2">
                                    <h5 class="font-semibold text-sm">${comment.user.name}</h5>
                                    <p class="text-sm">${comment.content}</p>
                                </div>
                                <div class="flex gap-2 mt-1">
                                    <button onclick="react(${comment.id}, 'comment', 'like')" class="text-xs text-gray-600 hover:text-blue-600">👍 Like</button>
                                    <button class="text-xs text-gray-600 hover:text-blue-600">Reply</button>
                                    <span class="text-xs text-gray-500">${new Date(comment.created_at).toLocaleString()}</span>
                                </div>
                                <div id="reactions-${comment.id}" class="mt-1"></div>
                            </div>
                        </div>
                    `;
                    commentsList.appendChild(commentDiv);
                    
                    // Load reactions for this comment
                    loadReactions(comment.id, 'comment');
                });
            }).catch(error => {
                console.error('Error loading comments:', error);
            });
        }

        function addComment(postId) {
            const input = document.getElementById(`comment-input-${postId}`);
            const content = input.value.trim();
            
            if (!content) {
                alert('Please enter a comment');
                return;
            }

            axios.post(`/posts/${postId}/comments`, {
                content: content
            }).then(response => {
                console.log('Comment added:', response.data);
                input.value = '';
                loadComments(postId);
            }).catch(error => {
                console.error('Error adding comment:', error);
                alert('Error adding comment. Check console.');
            });
        }

        function sharePost(postId) {
            const content = prompt('Add a message to your share (optional):');
            const groupId = prompt('Share to group ID (optional, leave empty for public):');
            
            axios.post(`/posts/${postId}/share`, {
                content: content || null,
                group_id: groupId || null,
                visibility: groupId ? 'group_only' : 'public'
            }).then(response => {
                console.log('Post shared:', response.data);
                alert('Post shared successfully!');
            }).catch(error => {
                console.error('Error sharing post:', error);
                alert('Error sharing post. Check console.');
            });
        }

        function createPost() {
            const content = document.getElementById('post-content').value.trim();
            const groupId = document.getElementById('post-group-id').value;
            const visibility = document.getElementById('post-visibility').value;

            if (!content) {
                alert('Please enter post content');
                return;
            }

            axios.post('/posts', {
                content: content,
                group_id: groupId || null,
                visibility: visibility
            }).then(response => {
                console.log('Post created:', response.data);
                document.getElementById('post-content').value = '';
                document.getElementById('post-group-id').value = '';
                alert('Post created successfully!');
                loadGlobalPosts(); // Refresh posts
            }).catch(error => {
                console.error('Error creating post:', error);
                alert('Error creating post. Check console.');
            });
        }

        // Auto-load groups on page load
        window.addEventListener('load', function() {
            // You can uncomment this if you want to auto-load
            // loadGroups();
        });

        function testAPI() {
            console.log('Testing API with current token...');
            
            // Test user endpoint first
            axios.get('/user')
                .then(response => {
                    console.log('User API works:', response.data);
                    
                    // Create a test post first
                    return axios.post('/posts', {
                        content: 'Test post for reactions',
                        visibility: 'public'
                    });
                })
                .then(response => {
                    console.log('Post created:', response.data);
                    const postId = response.data.data.id;
                    
                    // Test reactions endpoint with the new post
                    return axios.post('/reactions', {
                        target_type: 'post',
                        target_id: postId,
                        reaction_type: 'like'
                    });
                })
                .then(response => {
                    console.log('Reactions API works:', response.data);
                    alert('API test successful! Check console for details.');
                })
                .catch(error => {
                    console.error('API test failed:', error);
                    if (error.response) {
                        console.error('Error details:', error.response.data);
                        alert(`API test failed: ${error.response.data.message || error.message}`);
                    } else {
                        alert(`API test failed: ${error.message}`);
                    }
                });
        }
    </script>
</body>
</html>