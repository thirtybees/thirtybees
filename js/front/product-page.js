addEventListener("DOMContentLoaded", () => {
    const url = new URL(window.location.href);
    const combinationId = url.searchParams.get('combination');
    if (combinationId && window.combinationsFromController[combinationId]) {
        const combination = window.combinationsFromController[combinationId];
        url.searchParams.delete('combination');
        url.hash = combination.hashUrl;
        history.replaceState(history.state, '', url);
    }
});
