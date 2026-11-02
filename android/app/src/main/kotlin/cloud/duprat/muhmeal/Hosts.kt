package cloud.duprat.muhmeal

class Hosts(private val app: String?) {
    fun opensInApp(host: String?) = host != null && (host == app || host == ACCOUNTS)

    fun showsOverLockScreen(host: String?, path: String?) =
        host != null && host == app && path != null && (path == GROCERIES || path.startsWith("$GROCERIES/"))

    fun returnedFromAccounts(previous: String?, host: String?) = host != null && host == app && previous == ACCOUNTS

    private companion object {
        const val ACCOUNTS = "accounts.mossyleaf.studio"
        const val GROCERIES = "/groceries"
    }
}
