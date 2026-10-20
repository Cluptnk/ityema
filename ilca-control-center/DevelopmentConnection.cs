using System.Text.RegularExpressions;
using MySqlConnector;

namespace Itema.Ilca.ControlCenter;

internal static class DevelopmentConnection
{
    private const string DevelopmentPhpConfig = @"C:\Apache24\htdocs-dev\connectionDB.php";

    public static bool TryLoad(out string connectionString, out string error)
    {
        connectionString = string.Empty;
        error = string.Empty;
        try
        {
            if (!File.Exists(DevelopmentPhpConfig))
            {
                error = $"Geliştirme site ayarı bulunamadı: {DevelopmentPhpConfig}";
                return false;
            }

            var php = File.ReadAllText(DevelopmentPhpConfig);
            var dsn = GetValue(php, "dsn");
            var user = GetValue(php, "user");
            var password = GetValue(php, "pw");
            if (string.IsNullOrWhiteSpace(dsn) || string.IsNullOrWhiteSpace(user))
            {
                error = "Geliştirme site ayarındaki veritabanı bilgileri okunamadı.";
                return false;
            }

            var pairs = dsn.Replace("mysql:", string.Empty, StringComparison.OrdinalIgnoreCase)
                .Split(';', StringSplitOptions.RemoveEmptyEntries)
                .Select(part => part.Split('=', 2))
                .Where(part => part.Length == 2)
                .ToDictionary(part => part[0].Trim(), part => part[1].Trim(), StringComparer.OrdinalIgnoreCase);
            var builder = new MySqlConnectionStringBuilder
            {
                Server = pairs.GetValueOrDefault("host", "127.0.0.1"),
                Database = pairs.GetValueOrDefault("dbname", string.Empty),
                UserID = user,
                Password = password,
                SslMode = MySqlSslMode.None
            };
            if (!string.Equals(builder.Database, "ilmsdb_dev", StringComparison.OrdinalIgnoreCase))
            {
                error = "Site ayarı geliştirme veritabanını göstermiyor; işlem durduruldu.";
                return false;
            }

            connectionString = builder.ConnectionString;
            return true;
        }
        catch (Exception exception)
        {
            error = exception.Message;
            return false;
        }
    }

    private static string GetValue(string php, string name)
    {
        var match = Regex.Match(php, @"\$" + Regex.Escape(name) + @"\s*=\s*'(?<value>(?:\\'|[^'])*)'", RegexOptions.CultureInvariant);
        return match.Success ? match.Groups["value"].Value.Replace("\\'", "'") : string.Empty;
    }
}
