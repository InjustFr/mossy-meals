package cloud.duprat.muhmeal

import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test

class HostsTest {
    private val hosts = Hosts("meals.example.com")

    @Test
    fun theAppAndAccountsHostsStayInTheApp() {
        assertTrue(hosts.opensInApp("meals.example.com"))
        assertTrue(hosts.opensInApp("accounts.duprat.cloud"))
    }

    @Test
    fun anyOtherHostOpensTheBrowser() {
        assertFalse(hosts.opensInApp("example.org"))
        assertFalse(hosts.opensInApp("accounts.duprat.cloud.example.org"))
        assertFalse(hosts.opensInApp(null))
    }

    @Test
    fun anUnreadableAppAddressNeverMatchesAMissingHost() {
        assertFalse(Hosts(null).opensInApp(null))
        assertFalse(Hosts(null).returnedFromAccounts("accounts.duprat.cloud", null))
    }

    @Test
    fun historyIsClearedOnlyWhenComingBackFromAccounts() {
        assertTrue(hosts.returnedFromAccounts("accounts.duprat.cloud", "meals.example.com"))
        assertFalse(hosts.returnedFromAccounts("meals.example.com", "meals.example.com"))
        assertFalse(hosts.returnedFromAccounts(null, "meals.example.com"))
        assertFalse(hosts.returnedFromAccounts("accounts.duprat.cloud", "accounts.duprat.cloud"))
    }

    @Test
    fun onlyTheGroceryPagesShowOverTheLockScreen() {
        assertTrue(hosts.showsOverLockScreen("meals.example.com", "/groceries"))
        assertTrue(hosts.showsOverLockScreen("meals.example.com", "/groceries/put-away"))
        assertFalse(hosts.showsOverLockScreen("meals.example.com", "/groceries-old"))
        assertFalse(hosts.showsOverLockScreen("meals.example.com", "/pantry"))
        assertFalse(hosts.showsOverLockScreen("accounts.duprat.cloud", "/groceries"))
        assertFalse(hosts.showsOverLockScreen("meals.example.com", null))
    }
}
