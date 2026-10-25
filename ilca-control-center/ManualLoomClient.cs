using System.Buffers.Binary;
using System.Net.Sockets;

namespace Itema.Ilca.ControlCenter;

// No constructor, timer, queue reader, retry loop or startup hook sends a command.
// The only production call is the manual dialog's explicit confirmed Send click.
internal static class ManualLoomClient
{
    public static async Task<byte[]> ExecuteAsync(ManualRequest request, CancellationToken cancellation)
    {
        var packet=ManualLoomProtocol.Build(request);
        if(ManualLoomProtocol.Upload(request.Operation)) {
            var status=request with {Operation=ManualOperation.StopStatus,FileBytes=[]};
            var state=await ExchangeAsync(status.Host,status.Port,ManualLoomProtocol.Build(status),cancellation);
            if(!ManualLoomProtocol.IsStopped(state,request.AirLoom!.Value)) throw new InvalidOperationException("Tezgâh tam duruşta değil. Dosya gönderilmedi. Önce uygun duruş işlemini siz uygulayın.");
        }
        var response=await ExchangeAsync(request.Host,request.Port,packet,cancellation);
        ManualLoomProtocol.CheckResponse(request.Operation,response);return response;
    }
    internal static async Task<byte[]> ExchangeAsync(string host,int port,byte[] packet,CancellationToken cancellation)
    {
        using var timeout=CancellationTokenSource.CreateLinkedTokenSource(cancellation);timeout.CancelAfter(TimeSpan.FromSeconds(30));
        using var client=new TcpClient();
        using(var connect=CancellationTokenSource.CreateLinkedTokenSource(timeout.Token)) {connect.CancelAfter(TimeSpan.FromSeconds(5));await client.ConnectAsync(host,port,connect.Token);}
        await using var stream=client.GetStream();await stream.WriteAsync(packet,timeout.Token);
        var header=new byte[10];await stream.ReadExactlyAsync(header,timeout.Token);
        int payload=BinaryPrimitives.ReadInt32LittleEndian(header.AsSpan(6,4));
        if(payload<1||payload>ManualLoomProtocol.MaximumFileBytes+256)throw new IOException("Yanıt uzunluğu geçersiz; başarı doğrulanamadı.");
        var response=new byte[10+payload];header.CopyTo(response,0);await stream.ReadExactlyAsync(response.AsMemory(10,payload),timeout.Token);return response;
    }
}
